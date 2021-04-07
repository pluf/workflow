# Converters

In order to declare state and event within *State* and *Transit*, user need to implement corresponding 
converters for their state and event type. The convert must implement Converter interface, 
which convert the state/event to/from String.

```php
interface Converter {
    /**
    * Convert object to string.
    * @param obj converted object
    * @return string description of object
    */
    function convertToString($obj): string;

    /**
    * Convert string to object.
    * @param name name of the object
    * @return converted object
    */
    function convertFromString(string $name);
}
```

Then register these converters to *ConverterProvider*. e.g.

```php
$converterProvider
	->register(MyEvent::class, MyEventConverter::class);
	->register(MyState.class, MyEventConverter::class);
```

*Note: If you only use fluent API to define state machine, there is no need to implement corresponding 
converters. And also if the Event or State class is type of String or primitives, you don't need to 
implement or register a converter explicitly at most of cases.*