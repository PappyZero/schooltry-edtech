<template>
  <AdminLayout>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ lesson.title }}</h1>
        <div class="flex space-x-3">
          <Link 
            :href="route('admin.lessons.edit', lesson.id)" 
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
          >
            Edit Lesson
          </Link>
          <Link 
            :href="route('lessons.show', lesson.id)" 
            target="_blank"
            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
          >
            View as Student
          </Link>
        </div>
      </div>

      <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            Lesson Content
          </h3>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
          <div class="prose max-w-none" v-html="formattedContent"></div>
        </div>
      </div>

      <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            Questions ({{ lesson.questions_count }})
          </h3>
        </div>
        
        <div v-if="lesson.questions && lesson.questions.length > 0" class="divide-y divide-gray-200">
          <div v-for="question in lesson.questions" :key="question.id" class="px-4 py-4 sm:px-6">
            <div class="flex justify-between">
              <h4 class="font-medium text-gray-900">{{ question.content }}</h4>
              <span class="text-sm text-gray-500">
                {{ new Date(question.created_at).toLocaleDateString() }}
              </span>
            </div>
            <div v-if="question.aiResponse" class="mt-2 p-3 bg-gray-50 rounded-md">
              <div class="prose prose-sm max-w-none" v-html="question.aiResponse.answer"></div>
              
              <div v-if="question.aiResponse.recommended_lessons && question.aiResponse.recommended_lessons.length > 0" class="mt-4">
                <h5 class="text-sm font-medium text-gray-700">Recommended Lessons:</h5>
                <ul class="list-disc pl-5 space-y-1">
                  <li v-for="lessonId in question.aiResponse.recommended_lessons" :key="lessonId">
                    <Link :href="route('admin.lessons.show', lessonId)" class="text-indigo-600 hover:text-indigo-900">
                      Lesson #{{ lessonId }}
                    </Link>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="px-4 py-5 sm:px-6 text-center text-gray-500">
          No questions have been asked about this lesson yet.
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';

defineProps({
  lesson: {
    type: Object,
    required: true
  }
});

const formattedContent = computed(() => {
  // Convert newlines to <p> tags for better display
  return lesson.content.replace(/\n/g, '</p><p>');
});
</script>
