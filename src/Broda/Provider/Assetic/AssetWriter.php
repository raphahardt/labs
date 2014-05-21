<?php

namespace Broda\Provider\Assetic;

use Assetic\Asset\AssetInterface;
use Assetic\AssetManager;
use Assetic\AssetWriter as BaseAssetWriter;
use Assetic\Factory\LazyAssetManager;

/**
 * Writes assets to the filesystem.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AssetWriter extends BaseAssetWriter
{
    public function writeManagerAssets(AssetManager $am)
    {
        foreach ($am->getNames() as $name) {
            // pega as configuracoes de cada formula
            $combine = true;
            if ($am instanceof LazyAssetManager) {
                list($inputs, $filters, $options) = $am->getFormula($name);
                if (isset($options['combine'])) {
                    $combine = $options['combine'];
                } else {
                    if (isset($options['debug'])) {
                        $combine = !$options['debug'];
                    } else {
                        $combine = !$am->isDebug();
                    }
                }
            }
            $this->writeAsset($am->get($name), $combine);
        }
    }

    public function writeAsset(AssetInterface $asset, $combine = true)
    {
        if ($combine) {
            $assets = array($asset);
        } else {
            $assets = $asset; // manda o proprio collection, que já é um
        }
        foreach ($assets as $asset) {
            parent::writeAsset($asset);
        }
    }
}
