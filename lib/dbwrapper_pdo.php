<?php
// addnews ready
// translator ready
// mail ready

/**
 * PDO-based Database Wrapper for Legend of the Green Dragon.
 * Provides backwards compatibility for legacy procedural calls,
 * while supporting prepared statements and parameterized inputs.
 */

$pdo_connection = null;
$pdo_last_statement = null;

function db_connect($host, $user, $pass, $database) {
    global $pdo_connection, $mysqli_resource;
    try {
        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT, // Mirror legacy silent error handling or handle manually
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo_connection = new PDO($dsn, $user, $pass, $options);
        $mysqli_resource = $pdo_connection;
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function db_pconnect($host, $user, $pass, $database) {
    global $pdo_connection, $mysqli_resource;
    try {
        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo_connection = new PDO($dsn, $user, $pass, $options);
        $mysqli_resource = $pdo_connection;
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function db_query($sql, $params = [], $die = true) {
    global $pdo_connection, $pdo_last_statement, $session, $dbinfo;

    // Handle backward compatibility for signature: db_query($sql, $die)
    if (is_bool($params)) {
        $die = $params;
        $params = [];
    }

    if (!defined('DB_NODB') && !defined('LINK')) return [];
    
    // Check installer restrictions (mirrored from mysqli_proc)
    $bt = debug_backtrace();
    $isTableDescriptor = str_contains($bt[0]['file'], 'tabledescriptor');
    $isInstaller = str_contains($bt[0]['file'], 'installer_stage');
    if (defined("IS_INSTALLER")
        && IS_INSTALLER !== false 
        && !$isTableDescriptor
        && !$isInstaller
    ) return [];

    $dbinfo['queriesthishit']++;
    $starttime = getmicrotime();

    if (!$pdo_connection) {
        require_once('dbconnect.php');
        db_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    }

    try {
        $stmt = $pdo_connection->prepare($sql);
        $pdo_last_statement = $stmt;
        
        $success = $stmt->execute($params);
        if (!$success) {
            throw new PDOException(implode(" - ", $stmt->errorInfo()));
        }
    } catch (PDOException $e) {
        if ($die === true) {
            if (defined("IS_INSTALLER")) {
                return [];
            } else {
                if ($session['user']['superuser'] & SU_DEVELOPER || 1) {
                    require_once("lib/show_backtrace.php");
                    die(
                        "<pre>" . htmlentities($sql, ENT_COMPAT, getsetting("charset", "ISO-8859-1")) . "</pre>"
                        . db_error()
                        . show_backtrace()
                    );
                } else {
                    die("A most bogus error has occurred.  I apologise, but the page you were trying to access is broken.  Please use your browser's back button and try again.");
                }
            }
        }
        return false;
    }

    $endtime = getmicrotime();
    if ($endtime - $starttime >= 1.00 && ($session['user']['superuser'] & SU_DEBUG_OUTPUT)) {
        $s = trim($sql);
        if (strlen($s) > 800) $s = substr($s, 0, 400) . " ... " . substr($s, strlen($s) - 400);
        debug("Slow Query (" . round($endtime - $starttime, 2) . "s): " . (htmlentities($s, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))) . "`n");
    }

    unset($dbinfo['affected_rows']);
    $dbinfo['affected_rows'] = db_affected_rows();
    if (!isset($dbinfo['querytime'])) $dbinfo['querytime'] = 0;
    $dbinfo['querytime'] += $endtime - $starttime;

    return $stmt;
}

function &db_query_cached($sql, $name, $duration = 900) {
    global $dbinfo;
    if (defined('IS_INSTALLER') && IS_INSTALLER === true) return [];
    
    $data = datacache($name, $duration);
    if (is_array($data)) {
        reset($data);
        $dbinfo['affected_rows'] = -1;
        return $data;
    } else {
        $result = db_query($sql);
        $data = [];
        if ($result) {
            while ($row = db_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        updatedatacache($name, $data);
        reset($data);
        return $data;
    }
}

function db_error() {
    global $pdo_connection, $pdo_last_statement;
    if (!$pdo_connection) {
        return "The database connection was never established";
    }
    if ($pdo_last_statement) {
        $info = $pdo_last_statement->errorInfo();
        return $info[2] ?? "";
    }
    $info = $pdo_connection->errorInfo();
    return $info[2] ?? "";
}

function db_fetch_assoc(&$result) {
    if (is_array($result)) {
        return array_shift($result);
    }
    if ($result instanceof PDOStatement) {
        return $result->fetch(PDO::FETCH_ASSOC);
    }
    return false;
}

function db_insert_id() {
    global $pdo_connection;
    if (!$pdo_connection) return -1;
    return $pdo_connection->lastInsertId();
}

function db_num_rows($result) {
    if (is_array($result)) {
        return count($result);
    }
    if ($result instanceof PDOStatement) {
        return $result->rowCount();
    }
    return 0;
}

function db_affected_rows($link = false) {
    global $dbinfo, $pdo_last_statement;
    if (isset($dbinfo['affected_rows'])) {
        return $dbinfo['affected_rows'];
    }
    if ($pdo_last_statement instanceof PDOStatement) {
        return $pdo_last_statement->rowCount();
    }
    return 0;
}

function db_get_server_version() {
    global $pdo_connection;
    if (!$pdo_connection) return "unknown";
    return $pdo_connection->getAttribute(PDO::ATTR_SERVER_VERSION);
}

function db_select_db($dbname) {
    global $pdo_connection;
    if (!$pdo_connection) return false;
    try {
        $pdo_connection->exec("USE `$dbname`");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function db_free_result($result) {
    if (is_array($result)) {
        unset($result);
    } elseif ($result instanceof PDOStatement) {
        $result->closeCursor();
    }
    return true;
}

function db_table_exists($tablename) {
    global $pdo_connection;
    if (!$pdo_connection) return false;
    try {
        $result = $pdo_connection->query("SHOW TABLES LIKE '$tablename'");
        return $result && $result->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function db_prefix($tablename, $force = false) {
    global $DB_PREFIX;
    if ($force === false) {
        $special_prefixes = [];
        if (file_exists("prefixes.php")) require_once("prefixes.php");

        $prefix = $DB_PREFIX;
        if (isset($special_prefixes[$tablename])) {
            $prefix = $special_prefixes[$tablename];
        }
    } else {
        $prefix = $force;
    }
    return $prefix . $tablename;
}

function db_escape($string) {
    global $pdo_connection;
    if (!$pdo_connection) {
        return addslashes($string);
    }
    $quoted = $pdo_connection->quote($string);
    if (strlen($quoted) >= 2 && $quoted[0] === "'" && substr($quoted, -1) === "'") {
        return substr($quoted, 1, -1);
    }
    return $quoted;
}
?>
