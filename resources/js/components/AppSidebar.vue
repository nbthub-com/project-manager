<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, LayoutGrid, List, Mail, Package, User as Person } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';

// Access user from Inertia props
const page = usePage();
const role = page.props.auth?.user?.role
const mainNavItems = computed<NavItem[]>(() => {
  const items: NavItem[] = [
    {
      title: 'Dashboard',
      href: role === 'admin' ? '/admin' : role === 'client' ? '/client' : '/',
      icon: LayoutGrid,
    },
    {
      title: 'Tasks',
      href: role === 'client' ? '/client/tasks' : '/tasks',
      icon: List,
    }
  ];

  if (role === 'admin') {
    items.push({
      title: 'Projects',
      href: '/admin/projects',
      icon: Package,
    });
    items.push({
      title: 'Members',
      href: '/admin/members',
      icon: Person,
    });
  } else if (role === 'client'){
    items.push({
      title: 'Projects',
      href: '/client/projects',
      icon: Package,
    });
  } else {
    items.push({
      title: 'Projects',
      href: '/projects',
      icon: Package,
    });
  }
  items.push({
      title: 'Mailbox',
      href: '/mailbox',
      icon: Mail,
    })

  return items;
});

const footerNavItems: NavItem[] = [
  {
    title: 'Documentation',
    href: '/docs',
    icon: BookOpen,
  },
];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="'/'">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="mainNavItems"/>
    </SidebarContent>

    <SidebarFooter>
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>
