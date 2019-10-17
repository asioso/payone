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

use PackageVersions\Versions;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

/**
 * Class PayoneBundle
 * @package PayoneBundle
 */
class PayoneBundle extends AbstractPimcoreBundle
{

    use PackageVersionTrait;
    const PACKAGE_NAME = 'asioso/pimcore-payone-module';

    public function getInstaller()
    {
        return $this->container->get(Installer::class);
    }

    public function getNiceName()
    {
        return  'Asioso - Payone Bundle';
    }

    /**
     * Bundle description as shown in extension manager
     *
     * @return string
     */
    public function getDescription()
    {
        return "";
    }


    public static function getSolutionVersion(){
        //code duplication from PackageVersionTrait... sorry
        $version = Versions::getVersion(self::PACKAGE_NAME);

        // normalizes v2.3.0@9e016f4898c464f5c895c17993416c551f1697d3 to 2.3.0
        $version = preg_replace('/^v/', '', $version);
        $version = preg_replace('/@(.+)$/', '', $version);

        return $version;
    }

    /**
     * Returns the composer package name used to resolve the version
     *
     * @return string
     */
    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }
}
