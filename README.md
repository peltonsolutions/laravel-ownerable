# laravel-ownerable

Laravel Ownerable, developed by Pelton Solutions, can be used to help make certain classes owned by other classes.
For example, in a tenant system, where a BlogPost might be owned by an Account. It automatically assigns the owner
and requires the owner when the owned class is queried. - test

## Install

You can install the package via composer using the following command:

``` bash
composer require peltonsolutions/laravel-ownerable
```

## Testing

To ensure that laravel-ownerable is functioning correctly, you can run the package's tests using:

``` bash
composer test
```

### Security

If you discover any security-related issues, please
email [security@peltonsolutions.com](mailto:security@peltonsolutions.com) instead of using the issue tracker.

## Credits

- [Nathan Pelton](https://www.nathanpelton.com)

## License

laravel-ownerable is open-sourced software. It's licensed under the [MIT license](https://opensource.org/licenses/MIT),
which is a permissive license allowing the software to be used, modified, and shared.
