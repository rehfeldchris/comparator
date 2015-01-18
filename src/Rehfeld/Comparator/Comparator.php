<?php

namespace Rehfeld\Comparator;

/**
 * This class has some functionality that helps make custom sorting more concise. Normally, you probably use usort()
 * everywhere, and all the callbacks that you write generally share 2 pieces of code:
 * -some code that extracts the sorting value
 * -some code that actually does comparisons upon the sorting value, to return -1, 0, or 1 in order to satisfy usort.
 *
 * This class tries to alleviate you from the duty of doing the comparisons - you just need to provide
 * the code to extract the value. And in basic cases, it can mostly alleviate you of that duty too.
 *
 * <code>
 * // Instead of writing code that both extracts the value and then does comparisons:
 * usort($arrayOfUser, function(User $a, User $b) {
 *      // Code that extracts the sorting value
 *      $dateA = $a->getBirthdayAsDatetime();
 *      $dateB = $b->getBirthdayAsDatetime();
 *
 *      // Code that compares the sorting value
 *      if ($dateA == $dateB) {
 *          return 0;
 *      }
 *      return $dateA < $dateB ? -1 : 1;
 *  });
 *
 * // Just provide a function which produces the sorting value:
 * usort($arrayOfUser, Comparator::sortAsc(function(User $user) {
 *     return $user->getBirthdayAsDatetime();
 * }));
 *
 * // In this scenario, since the sort value can be extracted with a single method call, you could also do:
 * usort($arrayOfUser, Comparator::sortAscByMethod('getBirthdayAsDatetime'));
 * </code>
 *
 * Not all values are comparable using the == and < operators, but for cases that are, this class can be used. Just beware,
 * loose comparisons don't always work like you may expect, in particular when the operands are different types.
 */
class Comparator
{
    /**
     * Creates a callable function suitable for use as a callback for php's usort function.
     *
     * Example how to sort an array of arrays:
     * <code>
     * $users = [
     *     ['name' => 'chris', 'birthday' => new DateTime('1980-01-15 01:15:33')],
     *     ['name' => 'jane',  'birthday' => new DateTime('1995-07-04 07:44:01')],
     * ];
     * $valueExtractor = function($array) {
     *     // Extract the hour of the day the user was born
     *     return $array['birthday']->format('G');
     * };
     * usort($users, Comparator::sortAscByValueExtractor($valueExtractor));
     * // Now, they're sorted ascending by the hour of the day they were born
     * </code>
     *
     * @param callable $valueExtractor
     * @return callable - A callable that accepts two parameters. After comparing the params, it will
     *                  return -1, 0, or 1 if the first param is less than, equal to, or greater
     *                  than the second param, respectively.
     */
    public static function sortAscByValueExtractor(callable $valueExtractor)
    {
        return self::sortByValueExtractor($valueExtractor, true);
    }

    public static function sortDescByValueExtractor(callable $valueExtractor)
    {
        return self::sortByValueExtractor($valueExtractor, false);
    }

    public static function sortByValueExtractor(callable $valueExtractor, $sortAscending)
    {
        return self::create($valueExtractor, (bool) $sortAscending);
    }

    public static function sortAscByMethod($methodName)
    {
        return self::sortByMethod($methodName, true);
    }

    public static function sortDescByMethod($methodName)
    {
        return self::sortByMethod($methodName, false);
    }

    public static function sortByMethod($methodName, $sortAscending)
    {
        $valueExtractor = function($object) use ($methodName) {
            return call_user_func(array($object, $methodName));
        };
        return self::create($valueExtractor, (bool) $sortAscending);
    }

    public static function sortAscByArrayKey($key)
    {
        return self::sortByArrayKey($key, true);
    }

    public static function sortDescByArrayKey($key)
    {
        return self::sortByArrayKey($key, false);
    }

    public static function sortByArrayKey($key, $sortAscending)
    {
        $valueExtractor = function($array) use ($key) {
            return $array[$key];
        };
        return self::create($valueExtractor, (bool) $sortAscending);
    }

    public static function sortAscByProperty($propertyName)
    {
        return self::sortByProperty($propertyName, true);
    }

    public static function sortDescByProperty($propertyName)
    {
        return self::sortByProperty($propertyName, false);
    }

    public static function sortByProperty($propertyName, $sortAscending)
    {
        $valueExtractor = function($object) use ($propertyName) {
            return $object->$propertyName;
        };
        return self::create($valueExtractor, (bool) $sortAscending);
    }

    private static function create(callable $valueExtractor, $sortAscending)
    {
        return ComparatorBuilder::create()
                                ->setValueExtractor($valueExtractor)
                                ->setSortAscending($sortAscending)
                                ->build();
    }

    /**
     * Produces a function which calls the provided comparators, in sequence, until one of them produces a non-0 value.
     * If one produces a non-0 value, it is immediately returned and no more comparators will be called.
     * If none of the comparators returns a non-0 value, then the value 0 will be returned.
     *
     * Use this function to mimic sql order by clause semantics.
     * @example
     * // Equivalent semantics to sql "... order by lastName asc, age desc, gender asc"
     * usort($array, Comparator::tieBreaker(
     *     Comparator::sortAscByArrayKey('lastName'),
     *     Comparator::sortDescByArrayKey('age'),
     *     Comparator::sortAscByArrayKey('gender'),
     * ));
     * @param callable $comparators,... - An array of comparators. They can either be the comparators returned by the methods in this class, or any
     *                                  callable which is suitable for use with php's native usort function.
     * @return callable
     */
    public static function tieBreaker($comparators)
    {
        $comparatorSequence = func_get_args();
        return function($a, $b) use($comparatorSequence) {
            foreach ($comparatorSequence as $comparator) {
                $diff = call_user_func($comparator, $a, $b);
                if ($diff !== 0) {
                    return $diff;
                }
            }
            return 0;
        };
    }
}