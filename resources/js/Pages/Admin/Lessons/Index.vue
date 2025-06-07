<template>
  <AdminLayout>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Lessons</h1>
      <Link 
        :href="route('admin.lessons.create')" 
        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
      >
        Create New Lesson
      </Link>
    </div>

    <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
      {{ $page.props.flash.success }}
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
      <div v-if="lessons.data.length > 0">
        <ul class="divide-y divide-gray-200">
          <li v-for="lesson in lessons.data" :key="lesson.id">
            <div class="px-4 py-4 sm:px-6">
              <div class="flex items-center justify-between">
                <Link :href="route('admin.lessons.edit', lesson.id)" class="text-indigo-600 hover:text-indigo-900 font-medium">
                  {{ lesson.title }}
                </Link>
                <div class="flex space-x-2">
                  <Link 
                    :href="route('admin.lessons.edit', lesson.id)" 
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    Edit
                  </Link>
                  <button 
                    @click="confirmDelete(lesson)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Delete
                  </button>
                </div>
              </div>
              <div class="mt-2 text-sm text-gray-500">
                Created {{ formatDate(lesson.created_at) }}
              </div>
              <div class="mt-1 text-sm text-gray-500">
                {{ lesson.questions_count }} questions
              </div>
            </div>
          </li>
        </ul>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
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
      <div v-else class="text-center p-8 text-gray-500">
        No lessons found. Create your first lesson to get started.
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal 
      :show="showDeleteModal" 
      @close="showDeleteModal = false"
      @confirm="deleteLesson"
      title="Delete Lesson"
      :message="`Are you sure you want to delete '${lessonToDelete?.title}'? This action cannot be undone.`"
      confirm-button-text="Delete Lesson"
      cancel-button-text="Cancel"
    >
      <template #footer>
        <SecondaryButton @click="showDeleteModal = false">
          Cancel
        </SecondaryButton>

        <DangerButton
          class="ml-3"
          :class="{ 'opacity-25': deleteInProgress }"
          :disabled="deleteInProgress"
          @click="deleteLesson"
        >
          Delete Lesson
        </DangerButton>
      </template>
    </ConfirmationModal>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
  lessons: Object,
});

const showDeleteModal = ref(false);
const deleteInProgress = ref(false);
const lessonToDelete = ref(null);

const confirmDelete = (lesson) => {
  lessonToDelete.value = lesson;
  showDeleteModal.value = true;
};

const deleteLesson = () => {
  if (!lessonToDelete.value) {
    showDeleteModal.value = false;
    return;
  }
  
  deleteInProgress.value = true;
  
  router.delete(route('admin.lessons.destroy', lessonToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
      deleteInProgress.value = false;
      // The page will automatically refresh due to Inertia's default behavior
    },
    onError: (errors) => {
      console.error('Failed to delete lesson:', errors);
      // You might want to show an error message to the user here
      alert('Failed to delete the lesson. Please try again.');
    },
    onFinish: () => {
      deleteInProgress.value = false;
    }
  });
};

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};
</script>