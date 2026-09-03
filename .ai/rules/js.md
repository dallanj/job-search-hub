---
paths:
  - '{app/Http/Controllers/PipelineController.php,app/PiniaHydrators/**,resources/js/pages/pipeline/**,resources/js/stores/**,resources/js/app.ts}'
---

# Js

## Hydrate pipeline state through its Pinia module
Register pipeline in config/pinia-hydrate.php and build named state through PipelineHydrator methods. Controllers emit the package's `$pinia` JSON prop with PiniaHydrate::replace(). App startup uses watchInertiaHydration for full/partial Inertia visits; pages consume usePipelineStore/storeToRefs and must not hydrate themselves or duplicate store state as page props.
