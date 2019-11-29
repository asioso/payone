<?php
/**
 * This source file is available under  GNU General Public License version 3 (GPLv3)
 *
 * Full copyright and license information is available in LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Asioso GmbH (https://www.asioso.com)
 *
 */

namespace PayoneBundle;


use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use PayoneBundle\Registry\CaptureHandler;
use PayoneBundle\Registry\Registry;
use Pimcore\Extension\Bundle\Installer\MigrationInstaller;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\DataObject\Objectbrick;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Migrations\MigrationManager;

/**
 * Class Installer
 * @package PayoneBundle
 */
class Installer extends MigrationInstaller
{

    /**
     * @var string
     */
    private $installSourcesPath;


    public function __construct(
        BundleInterface $bundle,
        ConnectionInterface $connection,
        MigrationManager $migrationManager
    ) {

        $this->installSourcesPath = __DIR__ . '/Resources/install';
        parent::__construct($bundle, $connection, $migrationManager);
    }

    /**
     * Executes install migration. Used during installation for initial creation of database tables and other data
     * structures (e.g. pimcore classes). The version object is the version object which can be used to add raw SQL
     * queries via `addSql`.
     *
     * If possible, use the Schema object to manipulate DB state (see Doctrine Migrations)
     *
     * @param Schema $schema
     * @param Version $version
     * @throws AbortMigrationException
     */
    public function migrateInstall(Schema $schema, Version $version)
    {
        $this->installDatabaseTable();
        $this->installBricks();
        return true;
    }

    /**
     * Opposite of migrateInstall called on uninstallation of a bundle.
     *
     * @param Schema $schema
     * @param Version $version
     */
    public function migrateUninstall(Schema $schema, Version $version)
    {
        //nothing
    }

    public function isInstalled()
    {
        $result = \Pimcore\Db::get()->fetchAll('SHOW TABLES LIKE "' . Registry::TABLE_NAME . '"');
        $resultLog = \Pimcore\Db::get()->fetchAll('SHOW TABLES LIKE "' . Registry::LOG_TABLE_NAME . '"');
        $captureLog =  \Pimcore\Db::get()->fetchAll('SHOW TABLES LIKE "' . CaptureHandler::LOG_TABLE_NAME . '"');

        return !empty($result) && !empty($resultLog) && !empty($captureLog) ;
    }

    public function canBeInstalled()
    {
        return !$this->isInstalled();
    }

    /**
     * {@inheritdoc}
     */
    public function needsReloadAfterInstall()
    {
        return false;
    }
    public function installDatabaseTable()
    {
        $sqlPath = __DIR__ . '/Resources/install/';
        $sqlFileNames = ['install.sql'];
        foreach ($sqlFileNames as $fileName) {
            $statement = file_get_contents($sqlPath.$fileName);
            \Pimcore\Db::get()->query($statement);
        }
    }

    public function installBricks()
    {
        $bricks = $this->findInstallFiles(
            $this->installSourcesPath . '/objectbrick_sources',
            '/^objectbrick_(.*)_export\.json$/'
        );
        foreach ($bricks as $key => $path) {
            if ($brick = Objectbrick\Definition::getByKey($key)) {
                $this->outputWriter->write(sprintf(
                    '     <comment>WARNING:</comment> Skipping object brick "%s" as it already exists',
                    $key
                ));

                continue;
            } else {
                $brick = new Objectbrick\Definition();
                $brick->setKey($key);
            }

            $data = file_get_contents($path);
            $success = Service::importObjectBrickFromJson($brick, $data);
            if (!$success) {
                throw new AbortMigrationException(sprintf(
                    'Failed to create object brick "%s"',
                    $key
                ));
            }
        }

    }

    /**
     * Finds objectbrick/fieldcollection sources by path returns a result list
     * indexed by element name.
     *
     * @param string $directory
     * @param string $pattern
     *
     * @return array
     */
    private function findInstallFiles(string $directory, string $pattern): array
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in($directory)
            ->name($pattern);
        $results = [];
        foreach ($finder as $file) {
            if (preg_match($pattern, $file->getFilename(), $matches)) {
                $key = $matches[1];
                $results[$key] = $file->getRealPath();
            }
        }
        return $results;
    }

}