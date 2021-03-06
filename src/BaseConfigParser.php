<?php
/**
 * This file is part of NoiseLabs-PHP-ToolKit
 *
 * NoiseLabs-PHP-ToolKit is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * NoiseLabs-PHP-ToolKit is distributed in the hope that it will be
 * useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with NoiseLabs-PHP-ToolKit; if not, see
 * <http://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2011-2013 Vítor Brandão <vitor@noiselabs.org>
 *
 *
 * @category    NoiseLabs
 * @package     ConfigParser
 * @copyright   (C) 2011-2013 Vítor Brandão <vitor@noiselabs.org>
 */

namespace KayzoreLabs\ToolKit\ConfigParser;

use RuntimeException;

/**
 * Base class for ConfigParser classes.
 */
abstract class BaseConfigParser implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * A set of internal options used when parsing and writing files.
     *
     * Known settings:
     *
     *  'delimiter':
     *      The delimiter character to use between keys and values.
     *      Defaults to '='.
     *
     *  'space_around_delimiters':
     *      Put a blank space between keys/values and delimiters?
     *      Defaults to TRUE.
     *
     *  'linebreak':
     *      The linebreak to use.
     *      Defaults to '\r\n' on Windows OS and '\n' on every other OS.
     *
     *  'interpolation':
     *      @todo: Describe the interpolation mecanism.
     *      Defaults to FALSE.
     */
    public $settings = [];

    /**
     *
     * @var array
     */
    protected $_defaults = [];

    /**
     * The configuration representation is stored here.
     * @var array
     */
    protected $_sections = [];

    /**
     * Comment lines are stored here so they can make it to the destination
     * file.
     *
     * @var array $_comments
     */
    protected $_comments;

    /**
     * An array of FILE objects representing the loaded files.
     * @var array
     */
    protected $_files = [];

    /**
     * Booleans alias
     * @var array
     */
    protected $_boolean_states = [
        '1'     => true,
        'yes'   => True,
        'true'  => true,
        'on'    => true,
        '0'     => false,
        'no'    => false,
        'false' => false,
        'off'   => false
    ];

    /**
     * Constructor.
     *
     * @param array $defaults
     * @param array $settings
     */
    public function __construct(array $defaults = [], array $settings = [])
    {
        $this->_defaults = $defaults;
        // default options
        $this->settings = new ParameterBag([
            'delimiter'                 => '=',
            'space_around_delimiters'   => true,
            'linebreak'                 => PHP_EOL,
            'throw_exceptions'          => true,
            'interpolation'             => false,
            'save_comments'             => true
        ]);

        $this->settings->add($settings);
    }

    /**
     * Return an associative array containing the instance-wide defaults.
     */
    public function defaults()
    {
        return $this->_defaults;
    }

    /**
     * Attempt to read and parse a list of filenames, returning a list of
     * filenames which were successfully parsed. If filenames is a string, it
     * is treated as a single filename. If a file named in filenames cannot be
     * opened, that file will be ignored. This is designed so that you can
     * specify a list of potential configuration file locations (for example,
     * the current directory, the user’s home directory, and some system-wide
     * directory), and all existing configuration files in the list will be
     * read. If none of the named files exist, the ConfigParser instance will
     * contain an empty dataset. An application which requires initial values
     * to be loaded from a file should load the required file or files using
     * read_file() before calling read() for any optional files:
     *
     * @param string|array $fileNames
     */
    public function read($fileNames = [])
    {
        if (!is_array($fileNames)) {
            $fileNames = [$fileNames];
        }

        foreach ($fileNames as $fileName) {
            if (is_readable($fileName)) {
                // register a new file...
                $file = new File($fileName, 'rb');
                $this->_files[] = $file;
                // ... and append configuration
                $this->_sections = array_replace($this->_sections, $this->_read($file));
            }
        }
    }

    public function readFile($filehandler)
    {
        trigger_error(__METHOD__.' is not implemented yet');
    }

    public function readString($string)
    {
        $this->_sections = parse_ini_string($string, static::HAS_SECTIONS, INI_SCANNER_RAW);
    }

    public function readArray(array $array = [])
    {
        $this->_sections = $array;
    }

    /**
     * Re-read configuration from all successfully parsed files.
     */
    public function reload()
    {
        foreach ($this->_files as $file) {
            $this->_sections = array_merge($this->_sections, $this->_read($file->getPathname()));
        }
    }

    /**
     * Write an .ini-format representation of the configuration state
     *
     * @param $fileName
     *
     * @return bool
     *
     * @throws RuntimeException if file is not writable
     */
    public function write($fileName)
    {
        $file = new File($fileName);

        if (!$file->open('wb')) {
            $errMsg = 'Unable to write configuration as file '.$file->getPathname().' could not be opened for writing';
            if ($this->_throwExceptions()) {
                throw new \RuntimeException($errMsg);
            } else {
                $this->log($errMsg);

                return false;
            }
        } elseif (!$file->isWritable()) {
            $errMsg = 'Unable to write configuration as file '.$file->getPathname().' is not writable';
            if ($this->_throwExceptions()) {
                throw new \RuntimeException($errMsg);
            } else {
                $this->log($errMsg);

                return false;
            }
        }

        $file->write($this->_buildOutputString());

        $file->close();

        return true;
    }

    /**
     * Returns the iterator for this group.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_sections);
    }

    /**
     * Returns the number of sections (implements the \Countable interface).
     *
     * @return integer The number of sections
     */
    public function count()
    {
        return count($this->_sections);
    }

    /**
     * Write the stored configuration to the last file successfully parsed
     * in $this->read().
     */
    public function save()
    {
        $file = end($this->_files);

        return $this->write($file->getPathname());
    }

    /**
     * Removes all parsed data.
     *
     * @return void
     */
    public function clear()
    {
        $this->_sections = [];
    }

    /**
     * Output the current configuration representation.
     *
     * @return array
     */
    public function dump()
    {
        return $this->_sections;
    }

    /**
     * Prints to the screen the current string as it would be written to the
     * configuration file.
     *
     * @return void
     */
    public function output()
    {
        echo $this->_buildOutputString();
    }

    /**
     * Remove the specified section from the configuration. If the section in
     * fact existed, return TRUE. Otherwise return FALSE.
     *
     * @param $section
     *
     * @return bool
     */
    public function removeSection($section)
    {
        if (true === $this->hasSection($section)) {
            unset($this->_sections[$section]);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns true if the section exists (implements the \ArrayAccess
     * interface).
     *
     * @param string $offset The name of the section
     *
     * @return Boolean true if the section exists, false otherwise
     */
    public function offsetExists($offset)
    {
        return $this->hasSection($offset);
    }

    /**
     * Returns the array of options associated with the section (implements
     * the \ArrayAccess interface).
     *
     * @param string $offset The offset of the value to get
     *
     * @return mixed The array of options associated with the section
     */
    public function offsetGet($offset)
    {
        return $this->hasSection($offset) ? $this->_sections[$offset] : null;
    }

    /**
     * Adds an array of options to the given section (implements the
     * \ArrayAccess interface).
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->_sections[$offset] = $value;
    }

    /**
     * Removes the child with the given name from the form (implements the
     * \ArrayAccess interface).
     *
     * @param string $name The name of the child to be removed
     */
    public function offsetUnset($name)
    {
        $this->remove($name);
    }

    public function log($message, $level = 'crit')
    {
        error_log($message);
    }

    /**
     * Saves all comments into an internal variable to be used when writing the
     * configuration to a file.
     *
     * @param string $filename
     */
    protected function readComments($filename)
    {
        $this->_comments = file($filename);

        foreach (array_keys($this->_comments) as $i) {
            if (substr(trim($this->_comments[$i]), 0, 1) != ';') {
                unset($this->_comments[$i]);
            }
        }
    }

    /**
     * Note the usage of INI_SCANNER_RAW to avoid parser_ini_files from
     * parsing options and transforming 'false' values to empty strings.
     *
     * @param File $file
     *
     * @return array|bool
     */
    protected function _read(File $file)
    {
        if (!$file->exists()) {
            $errmsg = 'File '.$file->getPathname().' does not exist.';
            if ($this->_throwExceptions()) {
                throw new \RuntimeException($errmsg);
            } else {
                $this->log($errmsg);

                return false;
            }
        }

        if (true === $this->settings->get('save_comments')) {
            $this->readComments($file->getPathname());
        }

        $contents = $this->sanitizeIniStructure($file->getContents());

        return parse_ini_string($contents, static::HAS_SECTIONS, INI_SCANNER_RAW);
    }

    abstract protected function _buildOutputString();

    protected function _throwExceptions()
    {
        return (false === $this->settings->get('throw_exceptions')) ? false : true;
    }

    /**
     * Sanitize the INI file structure because parse_ini_string() doesn't like
     * some of the features supported in Python's configparser.
     *
     * Hask marks:
     *  Since PHP version 5.3.0 "Hash marks (#) may no longer be used as comments
     *  and will throw a deprecation warning if used". To remain compatible with
     *  our Python counterpart we need make sure hash marks can be used in INI
     *  files so we replace them internally with semicolons (;) to please
     *  parse_ini_*() functions.
     *
     * @param  string $contents File contents as they come from the file.
     * @return string Sanitized contents
     */
    protected function sanitizeIniStructure($contents)
    {
        $lines = explode("\n", $contents);
        foreach (array_keys($lines) as $k) {
            // remove leading whitespace
            $lines[$k] = preg_replace('/(^\s*)(.*$)/', '${2}', $lines[$k]);

            // replace hash marks with semicolons
            $lines[$k] = preg_replace('/(^#)/', ';', $lines[$k], 1, $count);

            // replace the delimiter ":" with "="
            if ($count == 0) {
                $lines[$k] = preg_replace('/([^:])(:)/', '${1} =', $lines[$k], 1, $count);
            }

            $lines[$k] = str_replace("\"", "", $lines[$k]);
        }

        return implode("\n", $lines);
    }
}
