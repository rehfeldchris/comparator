#What is it

This class has some functionality that helps make custom sorting more concise. Normally, you probably use `usort()`
everywhere, and all the callbacks that you write generally share 2 pieces of functionality:  

1. some code that extracts the sorting value
2. some code that actually does comparisons upon the sorting value, to return -1, 0, or 1 in order to satisfy `usort()`.

This class tries to alleviate you from the duty of doing the comparisons - you just need to provide
the code to extract the value. And in basic cases, it can mostly alleviate you of that duty too.

Instead of writing code that both extracts the value and then does comparisons:  

    usort($arrayOfUser, function(User $a, User $b) {
        // Code that extracts the sorting value
        $dateA = $a->getBirthdayAsDatetime();
        $dateB = $b->getBirthdayAsDatetime();
    
        // Code that compares the sorting value
        if ($dateA == $dateB) {
            return 0;
        }
        return $dateA < $dateB ? -1 : 1;
    });

Just provide a function which produces the sorting value:  

    usort($arrayOfUser, Comparator::sortAscByValueExtractor(function(User $user) {
        return $user->getBirthdayAsDatetime();
    }));
    
In this scenario, since the sort value can be extracted with a single method call, you could also do:  

    usort($arrayOfUser, Comparator::sortAscByMethod('getBirthdayAsDatetime'));

Convenience methods for sorting by an array key, a public object property, or a public method exist. But,
the most powerful are the valueExtractor methods, because they let you use custom logic.

Another method that's included gives you the ability to sort using the same semantics that an sql order by clause uses.  The following would sort
the same way as the sql `... order by lastName asc, age desc, gender asc`  

    $arrayOfRows = [
        ['lastName' => 'smith', 'age' > 25, 'gender' => 'male'],
        ['lastName' => 'hax',   'age' > 33, 'gender' => 'male'],
    ];
    usort($arrayOfRows, Comparator::tieBreaker(
        Comparator::sortAscByArrayKey('lastName'),
        Comparator::sortDescByArrayKey('age'),
        Comparator::sortAscByArrayKey('gender'),
    ));
     
     
###Note
This class internally uses the loose comparison operators `==` and `<` by default, but not all values are be correctly comparable by using them. In particular, when you're comparing
different types, loose comparison operators can behave oddly. You can use the `ComparatorBuilder` class directly, and supply a custom
valueComparator function if you need to force string comparison semantics by using, for example, `strcmp`. But, you may 
consider just not using this class at all in this scenario - you're not really gaining much over just writing your own
`usort()` callback from scratch.


#Install
Get it with composer.

#Tests
A test suite with 100% code coverage is bundled. You can run it by cd'ing to the directory of this project, and running the command:  

    ./vendor/bin/phpunit