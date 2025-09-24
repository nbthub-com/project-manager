<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import { edit } from '@/routes/password'

import AppLayout from '@/layouts/AppLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue'
import HeadingSmall from '@/components/HeadingSmall.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { type BreadcrumbItem } from '@/types'

const breadcrumbItems: BreadcrumbItem[] = [
  { title: 'Password settings', href: edit().url },
]

const passwordInput = ref<HTMLInputElement | null>(null)
const currentPasswordInput = ref<HTMLInputElement | null>(null)

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.put(edit().url, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('password', 'password_confirmation', 'current_password')
    },
    onError: () => {
      // optionally reset sensitive fields on error too
      form.reset('password', 'password_confirmation')
      if (passwordInput.value) passwordInput.value.focus()
    },
  })
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <Head title="Password settings" />

    <SettingsLayout>
      <div class="space-y-6">
        <HeadingSmall
          title="Update password"
          description="Ensure your account is using a long, random password to stay secure"
        />

        <form @submit.prevent="submit" class="space-y-6">
          <div class="grid gap-2">
            <Label for="current_password">Current password</Label>
            <Input
              id="current_password"
              ref="currentPasswordInput"
              v-model="form.current_password"
              type="password"
              class="mt-1 block w-full"
              autocomplete="current-password"
              placeholder="Current password"
            />
            <InputError :message="form.errors.current_password" />
          </div>

          <div class="grid gap-2">
            <Label for="password">New password</Label>
            <Input
              id="password"
              ref="passwordInput"
              v-model="form.password"
              type="password"
              class="mt-1 block w-full"
              autocomplete="new-password"
              placeholder="New password"
            />
            <InputError :message="form.errors.password" />
          </div>

          <div class="grid gap-2">
            <Label for="password_confirmation">Confirm password</Label>
            <Input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              class="mt-1 block w-full"
              autocomplete="new-password"
              placeholder="Confirm password"
            />
            <InputError :message="form.errors.password_confirmation" />
          </div>

          <div class="flex items-center gap-4">
            <Button :disabled="form.processing">Save password</Button>

            <Transition
              enter-active-class="transition ease-in-out"
              enter-from-class="opacity-0"
              leave-active-class="transition ease-in-out"
              leave-to-class="opacity-0"
            >
              <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">
                Saved.
              </p>
            </Transition>
          </div>
        </form>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
