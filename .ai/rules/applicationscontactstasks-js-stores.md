---
paths:
  - '{app/Http/Controllers/{JobApplicationController.php,ContactController.php,TaskController.php},resources/js/pages/{applications,contacts,tasks}/Index.vue,resources/js/stores/{applications,contacts,tasks}.ts}'
---

# Applicationscontactstasks Js Stores

## Hydrate filtered index results through Pinia
Applications, contacts, and tasks index controllers emit replace-mode `$pinia` modules for result collections and display options. Their pages consume setup stores via storeToRefs; editable query filters remain page-local. Text inputs call useDebounceFn from explicit update:modelValue handlers, while selects/tabs search immediately with partial `$pinia` visits.
