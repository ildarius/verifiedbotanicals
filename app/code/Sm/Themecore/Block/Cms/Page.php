<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sm\Themecore\Block\Cms;

/**
 * Cms page content block
 *
 * @api
 * @since 100.0.2
 */
class Page extends \Magento\Cms\Block\Page
{
    protected $_imageBlank = '';
    
	protected function _toHtml()
    {
		$page = $this->getPage();
		$content = $this->decodePageBuilderHtmlContent((string) $page->getContent());
		$filter = clone $this->_filterProvider->getPageFilter();
		$filter->setStoreId((int) $this->_storeManager->getStore()->getId());
		$html = $filter->filter($content);
		if (strpos($html, '{{widget') !== false) {
			$html = $this->renderRemainingWidgets($html);
		}
		$_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$helper_config = $_objectManager->get('Sm\Themecore\Helper\Data');
		$useLazyload = $helper_config->getAdvanced('lazyload_group/enable_ladyloading'); /*add config Lazyload*/
		if ($useLazyload && !empty($html)) {
			$storeManager = $_objectManager->get('Magento\Store\Model\StoreManagerInterface');
			$currentStore = $storeManager->getStore();
			$mediaUrl = $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
			$this->_imageBlank = $mediaUrl.'lazyloading/blank.png';
			$html = $this->_usedLazyLoad($html);
		}
		return $html;
    }

	private function renderRemainingWidgets($html)
	{
		$storeId = (int) $this->_storeManager->getStore()->getId();
		$filter = clone $this->_filterProvider->getPageFilter();
		$filter->setStoreId($storeId);

		return preg_replace_callback(
			'/\{\{widget\b.*?\}\}/s',
			function ($matches) use ($filter) {
				$rendered = $filter->filter($matches[0]);
				return is_string($rendered) && $rendered !== '' ? $rendered : $matches[0];
			},
			$html
		);
	}

	private function decodePageBuilderHtmlContent($content)
	{
		if (strpos($content, 'data-content-type="html"') === false || strpos($content, '{{widget') === false) {
			return $content;
		}

		$document = new \DOMDocument('1.0', 'UTF-8');
		$wrappedContent = '<html><body>' . mb_encode_numericentity(
			$content,
			[0x80, 0x10FFFF, 0, 0x1FFFFF],
			'UTF-8'
		) . '</body></html>';

		libxml_use_internal_errors(true);
		$document->loadHTML($wrappedContent, LIBXML_SCHEMA_CREATE);
		libxml_clear_errors();

		$xpath = new \DOMXPath($document);
		$htmlNodes = $xpath->query(
			'//*[@data-content-type="html"][not(ancestor::*[@data-content-type="html"])]'
		);

		if (!$htmlNodes || !$htmlNodes->length) {
			return $content;
		}

		$decodedNodes = [];
		foreach ($htmlNodes as $htmlNode) {
			if (!strlen(trim($htmlNode->nodeValue)) || strpos($htmlNode->nodeValue, '{{widget') === false) {
				continue;
			}

			$clonedHtmlNode = clone $htmlNode;
			$clonedHtmlNode->nodeValue = '%s';

			while ($htmlNode->attributes->length) {
				$htmlNode->removeAttribute($htmlNode->attributes->item(0)->name);
			}

			$preDecodedOuterHtml = $document->saveHTML($htmlNode);
			$decodedInnerHtml = preg_replace(
				'#^<[^>]*>|</[^>]*>$#',
				'',
				html_entity_decode($preDecodedOuterHtml)
			);
			$decodedOuterHtml = sprintf($document->saveHTML($clonedHtmlNode), $decodedInnerHtml);
			$placeholderName = 'smthemecore' . bin2hex(random_bytes(8));
			$placeholderNode = new \DOMElement($placeholderName);

			$htmlNode->parentNode->replaceChild($placeholderNode, $htmlNode);
			$decodedNodes[$placeholderName] = $decodedOuterHtml;
		}

		if (empty($decodedNodes)) {
			return $content;
		}

		preg_match('/<body>(.+)<\/body><\/html>$/si', $document->saveHTML(), $matches);
		if (empty($matches[1])) {
			return $content;
		}

		$decodedContent = $matches[1];
		foreach ($decodedNodes as $placeholderName => $decodedOuterHtml) {
			$decodedContent = str_replace(
				'<' . $placeholderName . '></' . $placeholderName . '>',
				$decodedOuterHtml,
				$decodedContent
			);
		}

		return $decodedContent;
	}
	
	private function _usedLazyLoad($html){
		return  preg_replace_callback('/<img(.*?)src=\"(.*?)\"(.*?)>/i', [$this, '_replaceCallback'], $html); 
	}	
	
	private function _replaceCallback($m)
    {
		preg_match_all("/<[^>]*class=\"(.*?)lazyload\"[^>]*>/i", $m[0], $matchLazy, PREG_SET_ORDER);
		preg_match_all("/<[^>]*class=\"(.*?)mark-lazy(.*?)\"[^>]*>/i", $m[0], $matchLazyCon, PREG_SET_ORDER);
		if(isset($m[0]) && empty($matchLazy) && !empty($matchLazyCon)) {
			foreach($m as $k => $n){
				if($k > 0 && isset($m[$k]) && strpos($m[$k],'mark-lazy')) {
					$classReplace = preg_replace("/class=\"(.*?)\"/i", 'class="$1 lazyload"', $m[$k]);
					$alt = isset($m[3]) && !empty($m[3]) && strpos($m[3],'alt=') ? $m[3] : '';
					return '<img '.$classReplace.' src="'.$this->_imageBlank.'" data-src="'.$m[2].'" '.$alt.'>';
				}
			}
		}else{
			return $m[0];
		}
    }

}
