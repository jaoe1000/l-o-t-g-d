<?php
/**
 * Test Environment Scaffolding for LotGD Modernization Tests
 * Author: LotGD Test Engineering
 * License: CC BY-NC-SA 2.0
 * Modified by jaoe1000
 */

declare(strict_types=1);

require_once __DIR__ . '/MockDb.php';

class TestEnvironment {
    private static string $rootDir = '';
    private static array $mockSettings = [];
    private static string $tempDir = '';

    public static function init(): void {
        self::$rootDir = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);
        self::ensureConstants();
        self::reset();
    }

    public static function getRootDir(): string {
        if (empty(self::$rootDir)) {
            self::$rootDir = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);
        }
        return self::$rootDir;
    }

    public static function reset(): void {
        global $session, $_GET, $_POST, $_COOKIE, $_SERVER;
        $session = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        self::$mockSettings = [
            'allowclans' => 1,
            'saveaccountsoutput' => 0,
            'defaultlanguage' => 'en',
            'servertime' => '12:00:00',
        ];
        MockDb::reset();
    }

    public static function setSessionUser(?array $userData): void {
        global $session;
        if ($userData === null) {
            unset($session['user']);
        } else {
            $session['user'] = $userData;
        }
    }

    public static function setSetting(string $key, $value): void {
        self::$mockSettings[$key] = $value;
    }

    public static function getSetting(string $key, $default = null) {
        return self::$mockSettings[$key] ?? $default;
    }

    public static function getTempDir(): string {
        if (empty(self::$tempDir)) {
            $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lotgd_tests_' . bin2hex(random_bytes(6));
            if (!is_dir($tmp)) {
                mkdir($tmp, 0777, true);
            }
            self::$tempDir = $tmp;
        }
        return self::$tempDir;
    }

    public static function cleanupTempDir(): void {
        if (!empty(self::$tempDir) && is_dir(self::$tempDir)) {
            self::recursiveRmdir(self::$tempDir);
            self::$tempDir = '';
        }
    }

    private static function recursiveRmdir(string $dir): void {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? self::recursiveRmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public static function ensureConstants(): void {
        $constants = [
            'SU_MEGAUSER' => 1,
            'SU_EDIT_USERS' => 2,
            'SU_EDIT_CONFIG' => 4,
            'SU_EDIT_MOUNTS' => 8,
            'SU_EDIT_CREATURES' => 16,
            'SU_EDIT_TAUNTS' => 32,
            'SU_MANAGE_MODULES' => 64,
            'SU_EDIT_EQUIPMENT' => 128,
            'SU_EDIT_PETITIONS' => 256,
            'SU_EDIT_COMMENTS' => 512,
            'SU_MODERATE_CLANS' => 1024,
            'SU_AUDIT_MODERATION' => 2048,
            'SU_POST_MOTD' => 4096,
            'SU_DEBUG_OUTPUT' => 8192,
            'SU_DEVELOPER' => 16384,
            'SU_IS_TRANSLATOR' => 32768,
            'SU_VIEW_SOURCE' => 65536,
            'CLAN_APPLICANT' => 0,
            'CLAN_MEMBER' => 10,
            'CLAN_OFFICER' => 20,
            'CLAN_LEADER' => 30,
            'CLAN_FOUNDER' => 31,
        ];
        foreach ($constants as $k => $v) {
            if (!defined($k)) {
                define($k, $v);
            }
        }
    }

    /**
     * Reads a project file securely
     */
    public static function readFile(string $relativePath): string {
        $path = self::getRootDir() . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
        if (!file_exists($path)) {
            throw new RuntimeException("File not found: $path");
        }
        return file_get_contents($path) ?: '';
    }

    /**
     * Checks if a project file exists
     */
    public static function fileExists(string $relativePath): bool {
        $path = self::getRootDir() . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
        return file_exists($path);
    }
}

TestEnvironment::init();
