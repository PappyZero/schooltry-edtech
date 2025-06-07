<template>
  <form @submit.prevent="submit">
    <div class="space-y-6">
      <div>
        <InputLabel for="title" value="Title" />
        <TextInput
          id="title"
          v-model="form.title"
          type="text"
          class="mt-1 block w-full"
          required
          autofocus
        />
        <InputError :message="form.errors.title" class="mt-2" />
      </div>

      <div>
        <InputLabel for="content" value="Content" />
        <textarea
          id="content"
          v-model="form.content"
          rows="15"
          class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
          required
        ></textarea>
        <InputError :message="form.errors.content" class="mt-2" />
      </div>

      <div class="flex items-center justify-end">
        <Link
          :href="cancelUrl"
          class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
        >
          Cancel
        </Link>
        <PrimaryButton
          type="submit"
          class="ml-3"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          {{ submitButtonText }}
        </PrimaryButton>
      </div>
    </div>
  </form>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
  lesson: {
    type: Object,
    default: () => ({
      title: '',
      content: ''
    })
  },
  submitButtonText: {
    type: String,
    default: 'Save'
  },
  cancelUrl: {
    type: String,
    default: route('admin.lessons.index')
  },
  method: {
    type: String,
    default: 'post'
  },
  action: {
    type: String,
    required: true
  }
});

const form = useForm({
  title: props.lesson.title,
  content: props.lesson.content
});

const submit = () => {
  if (props.method.toLowerCase() === 'put') {
    form.put(props.action, {
      onSuccess: () => {
        // The page will be refreshed automatically by Inertia
        // with the success message from the server
      },
      onError: (errors) => {
        console.error('Failed to update lesson:', errors);
      },
      preserveScroll: true
    });
  } else {
    form.post(props.action, {
      onSuccess: () => {
        // The page will be refreshed automatically by Inertia
        // with the success message from the server
      },
      onError: (errors) => {
        console.error('Failed to create lesson:', errors);
      },
      preserveScroll: true
    });
  }
};
</script>