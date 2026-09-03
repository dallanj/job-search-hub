---
paths:
  - '{app/Http/Controllers/PipelineController.php,app/PiniaLoaders/**,resources/js/pages/pipeline/**,resources/js/stores/**}'
---

# Stores

## Hydrate pipeline state through its Pinia module
Build pipeline read state in PipelineLoader and return it as the versioned `pinia` Inertia prop using replace mode. Register matching Laravel loaders and JavaScript stores explicitly. Pipeline Vue components consume reactive state through usePipelineStore/storeToRefs; do not duplicate columns, companies, or filters as top-level page props.

## Hydrate pipeline state through its Pinia module
Pipeline now uses the v0.2 module API: configure PipelineHydrator, emit `$pinia` with PiniaHydrate::replace(), and hydrate through the app-level watchInertiaHydration lifecycle. Pages consume usePipelineStore/storeToRefs and must not hydrate themselves or duplicate store state as page props.
