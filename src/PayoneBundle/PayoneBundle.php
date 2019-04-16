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

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

/**
 * Class PayoneBundle
 * @package PayoneBundle
 */
class PayoneBundle extends AbstractPimcoreBundle implements \Pimcore\Extension\Bundle\PimcoreBundleInterface
{

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

    public function getVersion()
    {
        return 'v0.1';
    }

    public static function getSolutionVersion(){
        return "v0.1";
    }

}
