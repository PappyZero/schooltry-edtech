<template>
  <StudentLayout :title="lesson.title">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <!-- Lesson Content -->
          <div class="prose max-w-none mb-8" v-html="lesson.content"></div>

          <!-- Refresh Notification -->
          <div v-if="showRefreshMessage" class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5a.75.75 0 001.5 0v-5zM10 14a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-yellow-800">
                  Please refresh the page in {{ countdown }} seconds to see the AI response
                </p>
              </div>
            </div>
          </div>

          <!-- Questions Section -->
          <div class="mt-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6">Questions</h2>

            <!-- Ask Question Form -->
            <form @submit.prevent="submitQuestion" class="mb-8">
              <div class="mb-4">
                <label for="question" class="block text-sm font-medium text-gray-700 mb-2">Ask a question about this lesson</label>
                <textarea
                  id="question"
                  v-model="form.content"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  :class="{ 'border-red-500': form.errors.content }"
                  placeholder="Type your question here..."
                  required
                ></textarea>
                <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">{{ form.errors.content }}</p>
              </div>
              <div class="flex justify-end">
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                  <span v-if="form.processing">Submitting...</span>
                  <span v-else>Ask Question</span>
                </button>
              </div>
            </form>

            <!-- Questions List -->
            <div class="space-y-6">
              <div
                v-for="question in questions"
                :key="question.id"
                :id="'question-' + question.id"
                class="border-b border-gray-200 pb-6 last:border-0 last:pb-0"
              >
                <div class="flex items-start">
                  <div class="flex-shrink-0 mr-3">
                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold">
                      {{ question.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                      <p class="text-sm font-medium text-gray-900">{{ question.user?.name || 'Unknown User' }}</p>
                      <span class="text-xs text-gray-500">{{ formatDate(question.created_at) }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-700 whitespace-pre-line">{{ question.content }}</p>

                    <!-- AI Response -->
                    <div v-if="question.ai_response" class="mt-4 p-4 bg-gray-50 rounded-md">
                      <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                          <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                            </svg>
                          </div>
                        </div>
                        <div class="flex-1 min-w-0">
                          <div class="flex items-center space-x-2 mb-2">
                            <span class="text-sm font-medium text-gray-900">AI Assistant</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                              AI
                            </span>
                          </div>

                          <div class="prose prose-sm max-w-none text-gray-700" v-html="formatAiResponse(question.ai_response.answer)"></div>

                          <!-- Recommended Lessons -->
                          <div v-if="question.ai_response.recommended_lessons && question.ai_response.recommended_lessons.length > 0" class="mt-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Recommended Lessons</h4>
                            <div class="grid grid-cols-1 gap-2">
                              <a
                                v-for="(lesson, index) in question.ai_response.recommended_lessons"
                                :key="index"
                                :href="lesson.url || '#'"
                                class="flex items-center px-3 py-2 bg-white border border-gray-200 rounded-md hover:bg-gray-50 transition-colors"
                              >
                                <span class="truncate">{{ typeof lesson === 'string' ? lesson : (lesson.title || 'Untitled Lesson') }}</span>
                                <svg class="ml-auto h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                              </a>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Loading State -->
                    <div v-else class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                      <div class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <div>
                          <p class="text-sm font-medium text-yellow-800">Generating AI response...</p>
                          <p class="text-xs text-yellow-700 mt-1">This may take a few moments. The response will appear here when ready.</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- No Questions -->
              <div v-if="questions.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No questions yet</h3>
                <p class="mt-1 text-sm text-gray-500">Be the first to ask a question about this lesson.</p>
              </div>
              

            </div>
          </div>
        </div>
      </div>
    </div>
  </StudentLayout>
</template>

<script setup>
import { ref, nextTick, onMounted, onUnmounted, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';

const props = defineProps({
  lesson: {
    type: Object,
    required: true,
  },
  questionId: {
    type: [String, Number],
    default: null,
  },
});

const form = useForm({
  content: '',
});

// Get questions from lesson.questions
const questions = ref([...props.lesson.questions || []]);
const loadingQuestions = ref({});
const showRefreshMessage = ref(false);
const refreshTimer = ref(null);
const countdown = ref(5);
let countdownInterval = null;

// Cancel the refresh
const cancelRefresh = () => {
  console.log('Cancelling refresh...');
  if (refreshTimer.value) {
    clearTimeout(refreshTimer.value);
    refreshTimer.value = null;
  }
  if (countdownInterval) {
    clearInterval(countdownInterval);
    countdownInterval = null;
  }
  showRefreshMessage.value = false;
  console.log('Refresh cancelled');
};

// Debug: Log the initial data
console.log('Lesson data:', props.lesson);
console.log('Initial questions:', questions.value);

// Watch for changes to lesson.questions and update the local ref
watch(() => props.lesson.questions, (newQuestions) => {
  console.log('Questions changed:', newQuestions);
  questions.value = [...(newQuestions || [])];
}, { immediate: true });

// Clean up timers when component is unmounted
onUnmounted(() => {
  if (refreshTimer.value) {
    clearTimeout(refreshTimer.value);
  }
  if (countdownInterval) {
    clearInterval(countdownInterval);
  }
});

// Format date for display
const formatDate = (dateString) => {
  if (!dateString) return '';

  const date = new Date(dateString);
  if (isNaN(date.getTime())) return dateString;

  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
};

// Format AI response to handle markdown and line breaks
const formatAiResponse = (text) => {
  if (!text) return '';

  // Convert markdown links to HTML
  let formattedText = text.replace(
    /\[([^\]]+)\]\(([^)]+)\)/g,
    '<a href="$2" class="text-blue-600 hover:underline" target="_blank" rel="noopener noreferrer">$1</a>'
  );

  // Convert line breaks to <br> and paragraphs
  formattedText = formattedText
    .split('\n\n')
    .map(paragraph => `<p class="mb-2">${paragraph.replace(/\n/g, '<br>')}</p>`)
    .join('');

  return formattedText;
};

// Submit a new question
const submitQuestion = async () => {
  console.log('1. Starting to submit question...');
  
  // Show refresh message immediately after form submission
  showRefreshMessage.value = true;
  countdown.value = 5;
  
  // Clear any existing timers
  if (refreshTimer.value) clearTimeout(refreshTimer.value);
  if (countdownInterval) clearInterval(countdownInterval);
  
  try {
    // Submit the form
    await form.post(route('lessons.questions.store', props.lesson.id), {
      preserveScroll: true,
      onSuccess: () => {
        console.log('2. Question submitted successfully');
        // Start the countdown
        countdownInterval = setInterval(() => {
          countdown.value--;
          console.log(`Refreshing in ${countdown.value} seconds...`);
          if (countdown.value <= 0) {
            clearInterval(countdownInterval);
            window.location.reload();
          }
        }, 1000);
      },
      onError: (errors) => {
        console.error('Error submitting question:', errors);
        showRefreshMessage.value = false;
      }
    });
    
    // Set a timeout to refresh after 5 seconds if the success callback doesn't fire
    refreshTimer.value = setTimeout(() => {
      console.log('Refreshing page after timeout...');
      if (countdownInterval) clearInterval(countdownInterval);
      window.location.reload();
    }, 5000);
    
  } catch (error) {
    console.error('Error in submitQuestion:', error);
    showRefreshMessage.value = false;
  }
};

// Fetch a single question with its AI response
const fetchQuestion = async (questionId) => {
  if (!questionId) return null;

  try {
    loadingQuestions.value[questionId] = true;

    const response = await fetch(`/api/questions/${questionId}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    const responseData = await response.json();
    const question = responseData.data;

    if (!question) {
      throw new Error('No question data received');
    }

    // Ensure the question has the expected structure
    const formattedQuestion = {
      ...question,
      // Ensure ai_response is properly formatted
      ai_response: question.ai_response ? {
        ...question.ai_response,
        // Ensure recommended_lessons is an array
        recommended_lessons: Array.isArray(question.ai_response.recommended_lessons)
          ? question.ai_response.recommended_lessons
          : []
      } : null
    };

    // Update the question in the questions array
    const index = questions.value.findIndex(q => q.id === formattedQuestion.id);
    if (index !== -1) {
      questions.value[index] = formattedQuestion;
    } else {
      questions.value.unshift(formattedQuestion);
    }

    return formattedQuestion;
  } catch (error) {
    console.error('Error fetching question:', error);
    throw error;
  } finally {
    loadingQuestions.value[questionId] = false;
  }
};

// Check if we need to scroll to a specific question on page load
onMounted(() => {
  if (props.questionId) {
    nextTick(() => {
      setTimeout(() => {
        const questionElement = document.getElementById(`question-${props.questionId}`);
        if (questionElement) {
          questionElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
          questionElement.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2');
          setTimeout(() => {
            questionElement.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2');
          }, 3000);
        }
      }, 500);
    });
  }
});


</script>
