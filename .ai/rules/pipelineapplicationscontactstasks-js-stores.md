---
paths:
  - '{app/Http/Controllers/{PipelineController.php,JobApplicationController.php,ContactController.php,TaskController.php},app/PiniaHydrators/**,resources/js/pages/{pipeline,applications,contacts,tasks}/Index.vue,resources/js/stores/**}'
---

# Pipelineapplicationscontactstasks Js Stores

## Centralize reusable options in the options module
Domain stores contain only domain result state. Shared companies, application statuses, task priorities, and future reusable select/label data belong to OptionsHydrator and useOptionsStore. Controllers replace only the option keys needed by a full page load; partial filtered visits omit options so existing option state is preserved.
