---
paths:
  - '{resources/js/pages/**,resources/js/stores/**,app/PiniaHydrators/**}'
---

# Pinia Hydrators

## Keep search parameters local to pages
Hydrate initial results and options into Composition API Pinia stores, then consume them with storeToRefs. Keep editable search/filter/pagination parameters in a page-local ref; explicit input handlers call existing store actions, text search uses useDebounceFn, and select/pagination changes search immediately. Do not use watchers or hydrate editable search parameters. Do not issue an initial client search when Laravel already hydrated the initial results.
