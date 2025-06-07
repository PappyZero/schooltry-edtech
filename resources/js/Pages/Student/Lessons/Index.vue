<template>
  <StudentLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Available Lessons
      </h2>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
          {{ $page.props.flash.success }}
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div v-if="lessons.data.length > 0" class="divide-y divide-gray-200">
            <div v-for="lesson in lessons.data" :key="lesson.id" class="p-6">
              <div class="flex justify-between">
                <div>
                  <Link :href="route('lessons.show', lesson.id)" class="text-lg font-medium text-indigo-600 hover:text-indigo-900">
                    {{ lesson.title }}
                  </Link>
                  <p class="mt-1 text-sm text-gray-500">
                    {{ lesson.questions_count }} questions
                  </p>
                </div>
                <div class="text-sm text-gray-500">
                  {{ formatDate(lesson.created_at) }}
                </div>
              </div>
            </div>
          </div>
          <div v-else class="p-6 text-center text-gray-500">
            No lessons available at the moment.
          </div>

          <!-- Pagination -->
          <div class="px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex-1 flex justify-between sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  Showing <span class="font-medium">{{ lessons.from }}</span> to 
                  <span class="font-medium">{{ lessons.to }}</span> of 
                  <span class="font-medium">{{ lessons.total }}</span> results
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                  <template v-for="(link, key) in lessons.links" :key="key">
                    <Link 
                      v-if="link.url"
                      :href="link.url"
                      v-html="link.label"
                      class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                      :class="{
                        'z-10 bg-indigo-50 border-indigo-500 text-indigo-600': link.active,
                        'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': !link.active,
                        'rounded-l-md': key === 0,
                        'rounded-r-md': key === lessons.links.length - 1
                      }"
                    />
                    <span 
                      v-else
                      v-html="link.label"
                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                    />
                  </template>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </StudentLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';

defineProps({
  lessons: {
    type: Object,
    required: true
  }
});

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};
</script>
