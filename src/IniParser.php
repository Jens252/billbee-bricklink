<?php

namespace BillbeeBricklink;

use Exception;

class IniParser
{
    private array|false $data = [];

    /**
     * IniParser constructor.
     * @param string $filePath Path to the INI file.
     * @throws Exception If the INI file is not found or is not valid.
     */
    public function __construct(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: $filePath");
        }

        $this->data = parse_ini_file($filePath, true, INI_SCANNER_TYPED);
        if ($this->data === false) {
            throw new Exception("Error parsing INI file: $filePath");
        }

        $this->setBaseUri();
    }

    /**
     * Get all parsed data.
     * @return array The parsed INI data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get a specific section or key from the INI data.
     * @param string $section The section name.
     * @param string|null $key The key name (optional).
     * @return mixed The value associated with the section or key.
     */
    public function get(string $section, string $key = null): mixed
    {
        if ($key === null) {
            return $this->data[$section] ?? null;
        }

        return $this->data[$section][$key] ?? null;
    }

    /**
     * Set a specific section or key in the INI data.
     * @param string $section The section name.
     * @param string $key The key name.
     * @param mixed $value The value to set.
     */
    public function set(string $section, string $key, mixed $value): void
    {
        $this->data[$section][$key] = $value;
    }

    /**
     * Check if a section exists in the INI data.
     * @param string $section The section name.
     * @return bool True if the section exists, false otherwise.
     */
    public function hasSection(string $section): bool
    {
        return isset($this->data[$section]);
    }

    /**
     * Check if a specific key exists in a section of the INI data.
     * @param string $section The section name.
     * @param string $key The key name.
     * @return bool True if the key exists, false otherwise.
     */
    public function hasKey(string $section, string $key): bool
    {
        return isset($this->data[$section][$key]);
    }

    /**
     * Convert INI data back to string format.
     * @return string The INI data as a string.
     */
    public function toString(): string
    {
        $output = '';
        foreach ($this->data as $section => $values) {
            $output .= "[$section]\n";
            foreach ($values as $key => $value) {
                $output .= "$key = \"$value\"\n";
            }
        }
        return $output;
    }

    /**
     * Set the base URI based on the use_https setting in the INI data.
     */
    private function setBaseUri(): void
    {
        $useHttps = $this->get("bricklink", "use_https") ?? true;
        if ($useHttps) {
            $this->set("bricklink", "base_uri", "https://api.bricklink.com/api/store/v1/");
        } else {
            trigger_error("It is recommended to use HTTPS links for security reasons.", E_USER_WARNING);
            $this->set("bricklink", "base_uri", "http://api.bricklink.com/api/store/v1/");
        }
    }
}
