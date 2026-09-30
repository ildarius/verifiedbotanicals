<?php
// PolyShell probe fixture: create simple product POLYSHELL-TEST (not visible, website 1) with a
// file-type custom option so dev/tools/polyshell_probe.sh reaches the file-processing code.
// Prints the option_id for the probe's OPTION_ID. Run from the Magento root:
//   php <this file>            create (idempotent)
//   php <this file> --delete   remove the product (quote rows expire normally)
use Magento\Framework\App\Bootstrap;
require getcwd() . '/app/bootstrap.php';
$om = Bootstrap::create(BP, $_SERVER)->getObjectManager();
$om->get(Magento\Framework\App\State::class)->setAreaCode('adminhtml');
$om->get(Magento\Store\Model\StoreManagerInterface::class)->setCurrentStore(0);
$repo = $om->get(Magento\Catalog\Api\ProductRepositoryInterface::class);
if (in_array('--delete', $argv, true)) {
    $om->get(Magento\Framework\Registry::class)->register('isSecureArea', true);
    try { $repo->deleteById('POLYSHELL-TEST'); echo "deleted POLYSHELL-TEST\n"; } catch (\Exception $e) { echo 'not found: ', $e->getMessage(), "\n"; }
    exit(0);
}
try { $p = $repo->get('POLYSHELL-TEST', true, 0, true); } catch (\Exception $e) {
    $p = $om->create(Magento\Catalog\Model\Product::class);
    $p->setSku('POLYSHELL-TEST')->setName('PolyShell Test')->setAttributeSetId(4)->setTypeId('simple')
      ->setPrice(1)->setStatus(1)->setVisibility(1)->setWebsiteIds([1])
      ->setStockData(['use_config_manage_stock' => 0, 'manage_stock' => 0, 'is_in_stock' => 1, 'qty' => 100]);
    $opt = $om->create(Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory::class)->create();
    $opt->setTitle('Upload')->setType('file')->setIsRequire(false)->setSortOrder(1)->setPrice(0)->setPriceType('fixed')
        ->setFileExtension('png, jpg')->setImageSizeX(0)->setImageSizeY(0)->setProductSku('POLYSHELL-TEST');
    $p->setOptions([$opt])->setCanSaveCustomOptions(true)->setHasOptions(1);
    $p = $repo->save($p);
    $p = $repo->get('POLYSHELL-TEST', false, 0, true);
}
foreach ($p->getOptions() as $o) { echo 'option_id=', $o->getOptionId(), ' type=', $o->getType(), PHP_EOL; }
