<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <span class="text-xl font-bold text-indigo-600">Admin Panel</span>
            </div>
            <div class="hidden sm:-my-px sm:ml-6 sm:flex sm:space-x-8">
              <NavLink :href="route('admin.lessons.index')" :active="route().current('admin.lessons.*')">
                Lessons
              </NavLink>
            </div>
          </div>
          <div class="hidden sm:ml-6 sm:flex sm:items-center">
            <!-- Profile dropdown -->
            <div class="ml-3 relative">
              <Dropdown>
                <DropdownTrigger>
                  <button class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span class="sr-only">Open user menu</span>
                    <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
                      {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                  </button>
                </DropdownTrigger>
                <template #content>
                  <DropdownLink :href="route('profile.edit')">
                    Profile
                  </DropdownLink>
                  <form @submit.prevent="logout">
                    <DropdownLink as="button">
                      Log Out
                    </DropdownLink>
                  </form>
                </template>
              </Dropdown>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page Content -->
    <main class="py-10">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import DropdownTrigger from '@/Components/DropdownTrigger.vue';
import NavLink from '@/Components/NavLink.vue';

const logout = () => {
  router.post(route('logout'));
};
</script>
