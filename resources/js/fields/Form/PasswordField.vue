<template>
  <DefaultField
    :field="currentField"
    :errors="errors"
    :show-help-text="showHelpText"
    :full-width-content="fullWidthContent"
  >
    <template #field>
      <input
        :id="currentField.uniqueKey"
        :dusk="field.attribute"
        type="password"
        v-model="value"
        class="w-full form-control form-input form-control-bordered"
        :class="errorClasses"
        :placeholder="placeholder"
        :autocomplete="currentField.autocomplete"
        :disabled="currentlyIsReadonly"
      />
    </template>
  </DefaultField>
</template>

<script>
import { DependentFormField, HandlesValidationErrors } from '@/mixins'

export default {
  mixins: [HandlesValidationErrors, DependentFormField],
}
</script>
