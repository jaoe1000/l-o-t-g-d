<?php
// addnews ready
// translator ready
// mail ready
// modified by jaoe1000

/**
 * Compatibility layer for NB-Core (Lotgd\*) and Doctrine DBAL classes
 * referenced by upstream modules in Legend of the Green Dragon.
 */

namespace Doctrine\DBAL {
    if (!class_exists('Doctrine\DBAL\ParameterType', false)) {
        class ParameterType
        {
            public const NULL = 0;
            public const INTEGER = 1;
            public const STRING = 2;
            public const LARGE_OBJECT = 3;
            public const BOOLEAN = 5;
            public const BINARY = 16;
            public const ASCII = 17;
        }
    }
}

namespace Lotgd {
    if (!class_exists('Lotgd\Output', false)) {
        class Output
        {
            public const VENDOR_BUCKET_PRE = 'pre';
            public const VENDOR_BUCKET_MID = 'mid';
            public const VENDOR_BUCKET_POST = 'post';

            private static ?self $instance = null;
            private static array $loadedAssets = [];

            public static function getInstance(): self
            {
                if (self::$instance === null) {
                    self::$instance = new self();
                }
                return self::$instance;
            }

            public function getColors(): array
            {
                return [
                    "1" => "colDkBlue",
                    "2" => "colDkGreen",
                    "3" => "colDkCyan",
                    "4" => "colDkRed",
                    "5" => "colDkMagenta",
                    "6" => "colDkYellow",
                    "7" => "colDkWhite",
                    "~" => "colBlack",
                    "!" => "colLtBlue",
                    "@" => "colLtGreen",
                    "#" => "colLtCyan",
                    "\$" => "colLtRed",
                    "%" => "colLtMagenta",
                    "^" => "colLtYellow",
                    "&" => "colLtWhite",
                    ")" => "colLtBlack",
                    "e" => "colDkRust",
                    "E" => "colLtRust",
                    "g" => "colXLtGreen",
                    "G" => "colXLtGreen",
                    "j" => "colMdGrey",
                    "J" => "colMdBlue",
                    "k" => "colaquamarine",
                    "K" => "coldarkseagreen",
                    "l" => "colDkLinkBlue",
                    "L" => "colLtLinkBlue",
                    "m" => "colwheat",
                    "M" => "coltan",
                    "p" => "collightsalmon",
                    "P" => "colsalmon",
                    "q" => "colDkOrange",
                    "Q" => "colLtOrange",
                    "R" => "colRose",
                    "T" => "colDkBrown",
                    "t" => "colLtBrown",
                    "V" => "colBlueViolet",
                    "v" => "coliceviolet",
                    "x" => "colburlywood",
                    "X" => "colbeige",
                    "y" => "colkhaki",
                    "Y" => "coldarkkhaki",
                ];
            }

            public static function output(...$args): void
            {
                \call_user_func_array('output', $args);
            }

            public static function outputNotl(...$args): void
            {
                \call_user_func_array('output_notl', $args);
            }

            public static function rawOutput(string $data): void
            {
                \rawoutput($data);
            }

            public static function appoencode(string $data, bool $priv = false): string
            {
                return \appoencode($data, $priv);
            }

            public static function requireVendorAsset(string $package, string $type, string $bucket = self::VENDOR_BUCKET_MID): void
            {
                $key = strtolower("$package:$type");
                if (isset(self::$loadedAssets[$key])) {
                    return;
                }
                self::$loadedAssets[$key] = true;

                if ($key === 'jquery:js') {
                    \rawoutput("<script src='https://code.jquery.com/jquery-3.7.1.min.js'></script>");
                } elseif ($key === 'datatables:css') {
                    \rawoutput("<link rel='stylesheet' href='https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css'>");
                } elseif ($key === 'datatables:js') {
                    \rawoutput("<script src='https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js'></script>");
                }
            }

            public static function addHeadMarkup(string $markup): void
            {
                \rawoutput($markup);
            }
        }
    }

    if (!class_exists('Lotgd\Nav', false)) {
        class Nav
        {
            public static function add($text, $link = false, $priv = false, $pop = false, $newwin = false): void
            {
                \addnav($text, $link, $priv, $pop, $newwin);
            }

            public static function addNotl($text, $link = false, $priv = false, $pop = false, $newwin = false): void
            {
                \addnav_notl($text, $link, $priv, $pop, $newwin);
            }

            public static function addHeader($text, $collapse = true): void
            {
                \addnav($text, false);
            }

            public static function addSubHeader($text): void
            {
                \addnav($text, false);
            }

            public static function addColoredHeader($text): void
            {
                \addnav($text, false);
            }

            public static function addColoredSubHeader($text): void
            {
                \addnav($text, false);
            }

            public static function blockNav($link, $partial = false): void
            {
                \blocknav($link, $partial);
            }
        }
    }

    if (!class_exists('Lotgd\Serialization', false)) {
        class Serialization
        {
            public static function safeUnserialize($data)
            {
                if (!is_string($data) || $data === '') {
                    return false;
                }
                return @\unserialize($data, ['allowed_classes' => false]);
            }
        }
    }

    if (!class_exists('Lotgd\SafeEscape', false)) {
        class SafeEscape
        {
            public static function escape($value): string
            {
                return \db_escape((string)$value);
            }
        }
    }

    if (!class_exists('Lotgd\Http', false)) {
        class Http
        {
            public static function get($var)
            {
                return \httpget($var);
            }

            public static function post($var)
            {
                return \httppost($var);
            }

            public static function allPost(): array
            {
                $res = \httpallpost();
                return is_array($res) ? $res : [];
            }

            public static function allGet(): array
            {
                $res = \httpallget();
                return is_array($res) ? $res : [];
            }

            public static function set($var, $val, $force = false): void
            {
                \httpset($var, $val, $force);
            }
        }
    }

    if (!class_exists('Lotgd\Forms', false)) {
        class Forms
        {
            public static function showForm($layout, $row, $nosave = false)
            {
                require_once('lib/showform.php');
                return \showform($layout, $row, $nosave);
            }

            public static function previewField($name, $startdiv = false, $talkline = 'says', $showcharsleft = true, $info = false, $default = '')
            {
                require_once('lib/forms.php');
                return \previewfield($name, $startdiv, $talkline, $showcharsleft, $info, $default);
            }
        }
    }

    if (!class_exists('Lotgd\Translator', false)) {
        class Translator
        {
            public static function translateInline($in, $schema = false)
            {
                return \translate_inline($in, $schema);
            }

            public static function sprintfTranslate(...$args)
            {
                return \call_user_func_array('sprintf_translate', $args);
            }

            public static function tlschema($schema = false): void
            {
                \tlschema($schema);
            }
        }
    }
}

namespace Lotgd\Nav {
    if (!class_exists('Lotgd\Nav\SuperuserNav', false)) {
        class SuperuserNav
        {
            public static function render(): void
            {
                require_once('lib/superusernav.php');
                \superusernav();
            }
        }
    }
}

namespace Lotgd\Security {
    if (!class_exists('Lotgd\Security\Csrf', false)) {
        class Csrf
        {
            public static function validatePostRequest(string $context = ''): bool
            {
                return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
            }

            public static function hiddenField(string $context = ''): string
            {
                return '';
            }

            public static function stripFrom(array $post): array
            {
                unset($post['_csrf_token']);
                return $post;
            }
        }
    }
}

namespace Lotgd\MySQL {
    if (!class_exists('Lotgd\MySQL\DoctrineResultCompat', false)) {
        class DoctrineResultCompat
        {
            private $result;

            public function __construct($result)
            {
                $this->result = $result;
            }

            public function fetchAssociative(): array|false
            {
                if (!$this->result) {
                    return false;
                }
                $row = \db_fetch_assoc($this->result);
                return is_array($row) ? $row : false;
            }

            public function fetchAllAssociative(): array
            {
                $rows = [];
                if (!$this->result) {
                    return $rows;
                }
                while ($row = \db_fetch_assoc($this->result)) {
                    $rows[] = $row;
                }
                return $rows;
            }

            public function fetchOne(): mixed
            {
                $row = $this->fetchAssociative();
                if ($row === false || empty($row)) {
                    return false;
                }
                return reset($row);
            }

            public function fetchFirstColumn(): array
            {
                $col = [];
                foreach ($this->fetchAllAssociative() as $row) {
                    if (!empty($row)) {
                        $col[] = reset($row);
                    }
                }
                return $col;
            }

            public function rowCount(): int
            {
                if (!$this->result) {
                    return 0;
                }
                return (int)\db_num_rows($this->result);
            }

            public function free(): void
            {
                if ($this->result) {
                    \db_free_result($this->result);
                }
            }
        }
    }

    if (!class_exists('Lotgd\MySQL\DoctrineConnectionCompat', false)) {
        class DoctrineConnectionCompat
        {
            private function interpolateNamedParams(string $sql, array $params, array $types = []): array
            {
                if (empty($params)) {
                    return [$sql, []];
                }
                // Check if array has string keys (named parameters)
                $isNamed = false;
                foreach (array_keys($params) as $k) {
                    if (is_string($k)) {
                        $isNamed = true;
                        break;
                    }
                }
                if (!$isNamed) {
                    return [$sql, array_values($params)];
                }

                // Sort keys by length descending so :module_check is replaced before :module
                $keys = array_keys($params);
                usort($keys, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

                foreach ($keys as $key) {
                    $paramKey = ltrim($key, ':');
                    $placeholder = ':' . $paramKey;
                    $val = $params[$key];
                    $type = $types[$key] ?? $types[$paramKey] ?? null;

                    if ($val === null || $type === \Doctrine\DBAL\ParameterType::NULL) {
                        $replacement = 'NULL';
                    } elseif ($type === \Doctrine\DBAL\ParameterType::INTEGER || is_int($val)) {
                        $replacement = (string)(int)$val;
                    } elseif ($type === \Doctrine\DBAL\ParameterType::BOOLEAN || is_bool($val)) {
                        $replacement = $val ? '1' : '0';
                    } elseif (is_float($val)) {
                        $replacement = (string)$val;
                    } elseif (is_array($val)) {
                        $escapedItems = array_map(static fn($v): string => "'" . \db_escape((string)$v) . "'", $val);
                        $replacement = implode(',', $escapedItems);
                    } else {
                        $replacement = "'" . \db_escape((string)$val) . "'";
                    }

                    $sql = preg_replace('/:' . preg_quote($paramKey, '/') . '\b/', $replacement, $sql);
                }

                return [$sql, []];
            }

            public function executeQuery(string $sql, array $params = [], array $types = []): DoctrineResultCompat
            {
                [$finalSql, $positionalParams] = $this->interpolateNamedParams($sql, $params, $types);
                $res = \db_query($finalSql, $positionalParams);
                return new DoctrineResultCompat($res);
            }

            public function executeStatement(string $sql, array $params = [], array $types = []): int
            {
                [$finalSql, $positionalParams] = $this->interpolateNamedParams($sql, $params, $types);
                \db_query($finalSql, $positionalParams);
                return (int)\db_affected_rows();
            }

            public function fetchAssociative(string $sql, array $params = [], array $types = []): array|false
            {
                return $this->executeQuery($sql, $params, $types)->fetchAssociative();
            }

            public function fetchAllAssociative(string $sql, array $params = [], array $types = []): array
            {
                return $this->executeQuery($sql, $params, $types)->fetchAllAssociative();
            }

            public function fetchOne(string $sql, array $params = [], array $types = []): mixed
            {
                return $this->executeQuery($sql, $params, $types)->fetchOne();
            }

            public function fetchFirstColumn(string $sql, array $params = [], array $types = []): array
            {
                return $this->executeQuery($sql, $params, $types)->fetchFirstColumn();
            }

            public function insert(string $table, array $data, array $types = []): int
            {
                if (empty($data)) {
                    return 0;
                }
                $cols = array_keys($data);
                $placeholders = array_fill(0, count($cols), '?');
                $sql = "INSERT INTO $table (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
                return $this->executeStatement($sql, array_values($data), $types);
            }

            public function update(string $table, array $data, array $criteria, array $types = []): int
            {
                if (empty($data)) {
                    return 0;
                }
                $sets = [];
                $params = [];
                foreach ($data as $col => $val) {
                    $sets[] = "$col = ?";
                    $params[] = $val;
                }
                $wheres = [];
                foreach ($criteria as $col => $val) {
                    if ($val === null) {
                        $wheres[] = "$col IS NULL";
                    } else {
                        $wheres[] = "$col = ?";
                        $params[] = $val;
                    }
                }
                $sql = "UPDATE $table SET " . implode(', ', $sets);
                if (!empty($wheres)) {
                    $sql .= " WHERE " . implode(' AND ', $wheres);
                }
                return $this->executeStatement($sql, $params, $types);
            }

            public function delete(string $table, array $criteria, array $types = []): int
            {
                $wheres = [];
                $params = [];
                foreach ($criteria as $col => $val) {
                    if ($val === null) {
                        $wheres[] = "$col IS NULL";
                    } else {
                        $wheres[] = "$col = ?";
                        $params[] = $val;
                    }
                }
                $sql = "DELETE FROM $table";
                if (!empty($wheres)) {
                    $sql .= " WHERE " . implode(' AND ', $wheres);
                }
                return $this->executeStatement($sql, $params, $types);
            }

            public function quote($value): string
            {
                if ($value === null) {
                    return 'NULL';
                }
                return "'" . \db_escape((string)$value) . "'";
            }

            public function lastInsertId(): int|string
            {
                return \db_insert_id();
            }
        }
    }

    if (!class_exists('Lotgd\MySQL\Database', false)) {
        class Database
        {
            private static ?DoctrineConnectionCompat $connection = null;

            public static function getDoctrineConnection(): DoctrineConnectionCompat
            {
                if (self::$connection === null) {
                    self::$connection = new DoctrineConnectionCompat();
                }
                return self::$connection;
            }

            public static function getInstance(): self
            {
                return new self();
            }

            public static function prefix(string $tablename, $force = false): string
            {
                return (string)\db_prefix($tablename, $force);
            }

            public static function query(string $sql, $die = true)
            {
                return \db_query($sql, [], (bool)$die);
            }

            public static function queryCached(string $sql, string $name, int $duration = 900)
            {
                return \db_query_cached($sql, $name, $duration);
            }

            public static function fetchAssoc(&$result)
            {
                return \db_fetch_assoc($result);
            }

            public static function numRows($result): int
            {
                return (int)\db_num_rows($result);
            }

            public static function affectedRows($link = false): int
            {
                return (int)\db_affected_rows($link);
            }

            public static function insertId()
            {
                return \db_insert_id();
            }

            public static function escape(string $string): string
            {
                return (string)\db_escape($string);
            }

            public static function freeResult($result): bool
            {
                return (bool)\db_free_result($result);
            }

            public static function error(): string
            {
                return (string)\db_error();
            }

            public static function tableExists(string $tablename): bool
            {
                return (bool)\db_table_exists($tablename);
            }
        }
    }
}
