<template>
  <AdminLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Create New Lesson
        </h2>
        <Link 
          :href="route('admin.lessons.index')" 
          class="text-sm text-indigo-600 hover:text-indigo-900"
        >
          &larr; Back to Lessons
        </Link>
      </div>
    </template>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
        {{ $page.props.flash.success }}
      </div>

      <div v-if="$page.props.errors && Object.keys($page.props.errors).length > 0" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
        <p class="font-bold">There was an error creating the lesson:</p>
        <ul class="list-disc list-inside">
          <li v-for="(error, field) in $page.props.errors" :key="field">
            {{ field }}: {{ error }}
          </li>
        </ul>
      </div>

      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
          <LessonForm 
            :action="route('admin.lessons.store')"
            :method="'post'"
            :submit-button-text="'Create Lesson'"
            :cancel-url="route('admin.lessons.index')"
          />
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import LessonForm from '@/Components/Forms/LessonForm.vue';
</script>
