# Mapping of TTSS ID to vehicle number

## Usage

Install dependencies by running:
```
composer update
```

To gather vehicle to ID mappings just run:
```
php parse.php
```

Output files `mapping_A.json` and `mapping_T.json` are put in `data` directory.

## Requirements

- [Composer](https://getcomposer.org/),
- PHP 7.0+,
- [PHP ftp extension](https://www.php.net/manual/en/ftp.installation.php),
- [PHP json extension](https://www.php.net/manual/en/json.installation.php),
- [PHP PDO extension](https://www.php.net/manual/en/pdo.installation.php),
- [PHP PDO SQLite extension](https://www.php.net/manual/en/ref.pdo-sqlite.php).
