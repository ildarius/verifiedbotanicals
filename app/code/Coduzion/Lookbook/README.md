# Mage2 Module Coduzion Lookbook

    ``coduzion/module-lookbook``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
Coduzion Lookbook

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Coduzion`
 - Enable the module by running `php bin/magento module:enable Coduzion_Lookbook`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

## Configuration

 - is_enabled (configuration/general/is_enabled)


## Specifications

 - API Endpoint
	- GET - Coduzion\Lookbook\Api\LookbookManagementInterface > Coduzion\Lookbook\Model\LookbookManagement

 - Block
	- Lookbook > lookbook.phtml

 - Controller
	- adminhtml > lookbook/index/index

 - Controller
	- adminhtml > lookbook/index/edit

 - Controller
	- adminhtml > lookbook/index/save

 - GraphQl Endpoint
	- Lookbooks

 - Helper
	- Coduzion\Lookbook\Helper\Data

 - Model
	- Lookbook

