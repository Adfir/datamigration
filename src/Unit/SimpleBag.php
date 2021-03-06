<?php

namespace Maketok\DataMigration\Unit;

class SimpleBag implements UnitBagInterface
{
    /**
     * @var UnitInterface[]
     */
    protected $units;
    /**
     * @var array
     */
    protected $levels = [];
    /**
     * @var array
     */
    protected $children = [];
    /**
     * @var array
     */
    protected $siblings = [];
    /**
     * @var bool
     */
    private $compiled = false;

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $units = array_values($this->units);
        // parent units should go first
        usort($units, function (UnitInterface $unit1, UnitInterface $unit2) use ($units) {
            $parent1 = $unit1->getParent();
            $parent2 = $unit2->getParent();
            if ($parent1 && !$parent2) {
                return 1;
            } elseif(!$parent1 && $parent2) {
                return -1;
            } else {
                if ($parent1 == $unit2) {
                    return 1;
                } elseif ($parent2 == $unit1) {
                    return -1;
                }
            }
            $ind1 = array_search($unit1, $units);
            $ind2 = array_search($unit2, $units);
            if ($ind1 > $ind2) {
                return 1;
            } elseif ($ind1 < $ind2) {
                return -1;
            }
            return 0;
        });
        return new \ArrayIterator($units);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->units);
    }

    /**
     * {@inheritdoc}
     */
    public function add(UnitInterface $unit)
    {
        $this->units[$unit->getCode()] = $unit;
    }

    /**
     * @param array $units
     */
    public function addSet(array $units)
    {
        foreach ($units as $unit) {
            $this->add($unit);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUnitByCode($code)
    {
        if (isset($this->units[$code])) {
            return $this->units[$code];
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function compileTree()
    {
        if ($this->compiled) {
            return;
        }
        $this->compileChildren();
        $this->compileLevels();
        $this->compileSiblings();
        $this->compiled = true;
    }

    /**
     * compile children array
     */
    protected function compileChildren()
    {
        foreach ($this->units as $parent) {
            foreach ($this->units as $unit) {
                if (($innerP = $unit->getParent()) && $innerP->getCode() == $parent->getCode()) {
                    $this->children[$parent->getCode()][] = $unit;
                }
            }
        }
    }

    /**
     * compile levels array
     */
    protected function compileLevels()
    {
        foreach ($this->units as $unit) {
            $toCheck = clone $unit;
            $parentLevel = 1;
            while (($parent = $toCheck->getParent()) !== null) {
                $parentLevel++;
                $toCheck = $parent;
            }
            $siblingsLevels = [];
            $i = 0;
            $siblings = $toCheck->getSiblings();
            foreach ($siblings as $siblingUnit) {
                $siblingsLevels[$i] = 1;
                while (($parent = $siblingUnit->getParent()) !== null) {
                    $siblingsLevels[$i]++;
                    $siblingUnit = $parent;
                }
                $i++;
            }
            if (count($siblingsLevels)) {
                $level = max(max($siblingsLevels), $parentLevel);
            } else {
                $level = $parentLevel;
            }
            $this->levels[$level][] = $unit->getCode();
        }
    }

    /**
     * Compile siblings array
     */
    public function compileSiblings()
    {
        foreach ($this->units as $unit) {
            $siblings = $unit->getSiblings();
            if (!empty($siblings)) {
                $mergedSet = array_merge([$unit->getCode() => $unit], $siblings);
                if (!in_array($mergedSet, $this->siblings)) {
                    $this->siblings[] = $mergedSet;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isLowest($code)
    {
        return $this->getUnitLevel($code) === $this->getLowestLevel();
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren($code)
    {
        return isset($this->children[$code]) ? $this->children[$code] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLowestLevel()
    {
        return max(max(array_keys($this->levels)), 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getUnitLevel($code)
    {
        foreach ($this->levels as $level => $codes) {
            if (in_array($code, $codes)) {
                return $level;
            }
        }
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getUnitsFromLevel($level)
    {
        return isset($this->levels[$level]) ? $this->levels[$level] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getRelations()
    {
        // from parent-child
        $sets =[];
        foreach ($this->children as $key => $units) {
            foreach ($units as $child) {
                /** @var UnitInterface $child */
                $sets[] = ['pc' => [$key => $this->getUnitByCode($key), $child->getCode() => $child]];
            }
        }
        // from siblings
        $siblingArrays = [];
        foreach ($this->siblings as $set) {
            $siblingArrays[] = ['s' => $set];
        }
        $sets = array_merge($siblingArrays, $sets);
        usort($sets, function ($valueA, $valueB) {
            $setA = array_pop($valueA);
            $setB = array_pop($valueB);
            $setALevels = array_map(function (UnitInterface $unit) {
                return $this->getUnitLevel($unit->getCode());
            }, $setA);
            $setBLevels = array_map(function (UnitInterface $unit) {
                return $this->getUnitLevel($unit->getCode());
            }, $setB);
            $averageA = array_sum($setALevels)/count($setALevels);
            $averageB = array_sum($setBLevels)/count($setBLevels);
            if ($averageA > $averageB) {
                return -1;
            } elseif ($averageB > $averageA) {
                return 1;
            }
            $codesA = array_map(function (UnitInterface $unit) {
                return $unit->getCode();
            }, $setA);
            $codesB = array_map(function (UnitInterface $unit) {
                return $unit->getCode();
            }, $setB);
            $codeAGlued = implode($codesA);
            $codeBGlued = implode($codesB);
            return strcmp($codeAGlued, $codeBGlued);
        });
        return $sets;
    }
}
