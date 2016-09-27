<?php

/**
 * @author Ioannis Botis
 * @date 23/9/2016
 * @version: Duplicity.php 11:26 am
 * @since 23/9/2016
 */
class Duplicity
{
    const DUPLICITY_CMD = 'duplicity';
    const DUPLICITY_CMD_SUFIX = '>/dev/null';

    private $_options = array(
        '--no-encryption' => array(
            'since' => '0.1',
            'use' => true,
        ),
        '--dry-run'  => array(
            'since' => '0.1',
            'use' => false,
        ),
    );

    /**
     * @var string the main directory to backup.
     */
    private $_main_directory;
    /**
     * @var string[] subdirectories of the main directory we want to exclude, eg cache, temp.
     */
    private $_excluded_directories;

    private $_destination;

    private static $_version;

    private $_output;

    public function __construct( $directory, $destination )
    {
        $this->_setMainDirectory( $directory );
        $this->_destination = $destination;
    }

    private function _setMainDirectory( $directory ) {
        if( ! self::isInstalled() ) {
            throw new Exception( 'Duplicity not installed' );
        }
        if( ! $this->directoryExists( $directory ) ) {
            throw new Exception( 'Directory path is invalid' );
        }
        $this->_main_directory = $directory;
    }

    /**
     * @param string $directory
     * @return bool
     */
    protected function directoryExists( $directory ) {
        return is_dir( $directory );
    }

    public static function isInstalled() {
        exec( self::DUPLICITY_CMD . ' -V' , $output, $exitCode );
        if( $exitCode ) {
            return false;
        }
        return true;
    }

    public static function getVersion() {
        if( isset( self::$_version ) ) {
            return self::$_version;
        }
        exec( self::DUPLICITY_CMD . ' -V' , $output, $exitCode );
        $output = implode( '', $output );
        return trim( str_replace( 'duplicity', '', $output ) );
    }

    /**
     * Verify backup, test that the backup is not corrupted and it can be restored.
     * When compare data is used, it compares files between source and destination location and exits with a non zero code.
     * Please note that the behaviour is different between versions <0.7 and >=0.7.
     * Versions <0.7 actually will compare data even if compare-data option is not used.
     * more info can be found here: https://bugs.launchpad.net/duplicity/+bug/1354880
     * @param bool $compare_data whether to compare data between source and destination for changes.
     * @return mixed
     */
    public function verify( $compare_data = true ) {
        self::_run( $this->_getOptions() . $this->_getExcludedPaths() . ' verify ' . ( $compare_data? '--compare-data file://' : '' ) . $this->_destination . ' ' . $this->_main_directory , $output, $exitCode );
        $this->_output = $output;
        return $exitCode;
    }

    public function execute() {
        self::_run( $this->_getOptions() . $this->_getExcludedPaths() . ' ' . $this->_main_directory . ' file://' . $this->_destination , $output, $exitCode );
        $this->_output = $output;
        return $exitCode;
    }

    public function getCollectionStatus() {
        self::_run(  $this->_getOptions() . $this->_getExcludedPaths() . ' collection-status file://' . $this->_destination, $output, $exitCode );
        $this->_output = $output;
        return $exitCode;
    }

    /**
     * @param $directory
     * @return mixed
     */
    public function restore( $time, $directory ) {
        if( ! $this->directoryExists( $directory ) ) {
            throw new Exception( 'Directory path is invalid' );
        }
        self::_run( $this->_getOptions() . $this->_getExcludedPaths() . ' restore file://' . $this->_destination . ' ' . $directory . ' --time=' . $time , $output, $exitCode );
        $this->_output = $output;
        return $exitCode;
    }

    private function _getExcludedPaths() {
        if( empty( $this->_excluded_directories ) ) {
            return '';
        }
        else {
            return '--exclude **' . implode( ' --exclude **', $this->_excluded_directories ) . ' ';
        }
    }

    private function _getOptions() {
        $options = array();

        foreach ( $this->_options as $option => $settings) {
            if( self::_isSupported( $settings[ 'since' ] ) ) {
                if( $settings[ 'use' ] ) {
                    $options[] = $option;
                }
            }
            else {
                trigger_error( 'Option ' . $option . ' is supported since ' . $settings[ 'since' ] . ',not in your local version');
            }
        }
        return implode( ' ', $options );
    }

    private static function _isSupported( $since ) {
        $version = self::getVersion();
        return version_compare( $version, $since, '>=' );
    }

    public function getOutput() {
        return $this->_output;
    }

    private static function _run( $cmd_parameters, &$output, &$exitCode ) {
        exec( self::DUPLICITY_CMD  . ' ' . $cmd_parameters . ' ' . static::DUPLICITY_CMD_SUFIX , $output, $exitCode );
        //echo self::DUPLICITY_CMD  . ' ' . $cmd_parameters . ' ' . static::DUPLICITY_CMD_SUFIX . "\n";
    }
}