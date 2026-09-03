---
paths:
  - '{app/Http/Controllers/PipelineController.php,app/PiniaHydrators/**,resources/js/pages/pipeline/**,resources/js/stores/**}'
---

# Js Stores

## Hydrate pipeline state through its Pinia module
Register pipeline in config/pinia-hydrate.php and expose named state from PipelineHydrator methods. Return the `$pinia` JSON prop via PiniaHydrate::replace(); app-level watchInertiaHydration handles visits. Pages only consume usePipelineStore/storeToRefs and do not hydrate themselves or duplicate store state as page props.
