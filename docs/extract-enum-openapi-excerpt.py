"""Extract the OpenAPI enum values cited by enum-openapi-audit.md."""

import hashlib
import json
import sys
from pathlib import Path


SOURCE_URL = "https://api.shop-pro.jp/v1/spec/open_api.json"

# JSON Pointers identify the compared response fields and the request exceptions.
# An empty mapping means the audit compared a description, not an enum constraint.
ENUM_PATHS = {
    "AuthRedirectUri": [],
    "AuthScope": [],
    "CategoryDisplayState": [
        "/components/schemas/productCategory/properties/display_state",
    ],
    "ContractPlan": [
        "/components/schemas/shop/properties/contract_plan",
    ],
    "DeliveryChargeFreeType": [
        "/components/schemas/delivery/properties/charge_free_type",
    ],
    "DeliveryChargeType": [
        "/components/schemas/delivery/properties/charge_type",
    ],
    "DeliveryMethodType": [
        "/components/schemas/delivery/properties/method_type",
    ],
    "DisplayState": [
        "/components/schemas/delivery/properties/display_state",
    ],
    "DomainPlan": [
        "/components/schemas/shop/properties/domain_plan",
    ],
    "ErrorCode": [
        "/paths/~1v1~1sales~1{sale_id}~1payment_urls/post/responses/401/content/application~1json/schema/properties/errors/items/properties/code",
    ],
    "ExternalAccountProvider": [
        "/components/schemas/customer/properties/external_accounts/items/properties/provider",
    ],
    "KouzaType": [
        "/components/schemas/payment/properties/financial/properties/kouza_type",
    ],
    "MailState": [
        "/components/schemas/sale/properties/accepted_mail_state",
        "/components/schemas/sale/properties/paid_mail_state",
        "/components/schemas/sale/properties/delivered_mail_state",
        "/paths/~1v1~1sales/get/parameters/11/schema",
        "/paths/~1v1~1sales/get/parameters/12/schema",
        "/paths/~1v1~1sales/get/parameters/13/schema",
    ],
    "MailType": [
        "/paths/~1v1~1sales~1{sale_id}~1mails/post/requestBody/content/application~1json/schema/properties/mail/properties/type",
    ],
    "OpenState": [
        "/components/schemas/shop/properties/open_state",
        "/components/schemas/shop/properties/mobile_open_state",
    ],
    "PaymentType": [
        "/components/schemas/payment/properties/type",
    ],
    "PointState": [
        "/components/schemas/sale/properties/point_state",
        "/components/schemas/sale/properties/gmo_point_state",
        "/components/schemas/sale/properties/yahoo_point_state",
        "/paths/~1v1~1sales~1{sale_id}/put/requestBody/content/application~1json/schema/properties/sale/properties/point_state",
    ],
    "Prefecture": [
        "/components/schemas/customer/properties/pref_id",
        "/components/schemas/shop/properties/pref_id",
    ],
    "ProductDisplayState": [
        "/components/schemas/productGroup/properties/display_state",
        "/components/schemas/productGroupCreateRequest/properties/group/properties/display_state",
        "/components/schemas/productGroupUpdateRequest/properties/group/properties/display_state",
    ],
    "Sex": [
        "/components/schemas/customer/properties/sex",
        "/components/schemas/sale/properties/customer/properties/sex",
        "/paths/~1v1~1customers/get/parameters/8/schema",
        "/paths/~1v1~1sales/post/requestBody/content/application~1json/schema/allOf/0/properties/sale/properties/customer/properties/sex",
    ],
    "ShopState": [
        "/components/schemas/shop/properties/state",
    ],
    "TaxRoundingMethod": [
        "/components/schemas/shop/properties/tax_rounding_method",
    ],
    "TaxType": [
        "/components/schemas/shop/properties/tax_type",
    ],
}


def at_pointer(document: object, pointer: str) -> object:
    node = document
    for encoded_part in pointer.lstrip("/").split("/"):
        part = encoded_part.replace("~1", "/").replace("~0", "~")
        node = node[int(part)] if isinstance(node, list) else node[part]
    return node


def main() -> None:
    if len(sys.argv) != 3:
        raise SystemExit("usage: extract-enum-openapi-excerpt.py OPENAPI_JSON YYYY-MM-DD")

    raw = Path(sys.argv[1]).read_bytes()
    document = json.loads(raw)
    entries = {}
    for name, pointers in ENUM_PATHS.items():
        entries[name] = {}
        for pointer in pointers:
            field = at_pointer(document, pointer)
            if not isinstance(field, dict):
                raise ValueError(f"{pointer} is not an OpenAPI field")
            values = field.get("enum")
            if values is not None and not isinstance(values, list):
                raise ValueError(f"{pointer}.enum is not an array")
            entries[name][f"{pointer}/enum"] = values

    print(json.dumps({
        "retrieved_at": sys.argv[2],
        "source_url": SOURCE_URL,
        "source_sha256": hashlib.sha256(raw).hexdigest(),
        "entries": entries,
    }, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
