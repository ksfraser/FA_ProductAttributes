# FR-PA-002-002 — Clone Parent Shipping Attributes to Variations

## Status

Fixed

## Requirement

After creating a variation product, `GenerateVariationsAction` shall call
`cloneShippingIfAvailable()` to copy the parent's shipping attributes to the
child. If no `ShippingAttributesDao` was injected, or the parent has no
shipping record, the operation is a silent no-op.

## Source

- GenerateVariationsAction::cloneShippingIfAvailable()

## Acceptance Criteria

1. If `$this->shippingDao` is null, return immediately.
2. Fetch the parent's shipping record via `get($parentId)`.
3. If null, return immediately.
4. Strip the `stock_id` key and call `upsert($childId, $data)`.
