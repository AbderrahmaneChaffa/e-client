@php
    $feed = auth()->check()
        ? app(\App\Services\Notifications\NotificationFeedService::class)->feed(auth()->user(), 12)
        : ['notifications' => [], 'unread_count' => 0];
@endphp

@auth
    <div
        x-data="notificationCenter({
            feedUrl: @js(route('notifications.feed')),
            markReadUrl: @js(route('notifications.read', ['notification' => '__ID__'])),
            markAllReadUrl: @js(route('notifications.read-all')),
            historyUrl: @js(route('notifications.index')),
            initialNotifications: @js($feed['notifications']),
            initialUnreadCount: @js($feed['unread_count']),
        })"
        x-init="init()"
        class="relative"
    >
        <button
            type="button"
            class="ui-icon-btn relative"
            @click="toggle()"
            :aria-expanded="open.toString()"
            aria-haspopup="true"
            aria-label="Notifications"
        >
            <i data-lucide="bell" class="h-4 w-4" aria-hidden="true"></i>
            <span
                x-show="unreadCount > 0"
                x-cloak
                x-text="displayCount"
                class="absolute -right-1 -top-1 h-4 min-w-4 rounded-full bg-danger-600 px-1 text-[10px] font-bold leading-4 text-white"
            ></span>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition
            @click.outside="open = false"
            class="absolute right-0 z-50 mt-3 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Notifications</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="unreadCountLabel"></p>
                </div>
                <button
                    type="button"
                    class="rounded-md px-2 py-1 text-xs font-semibold text-primary-700 hover:bg-primary-50 disabled:opacity-50 dark:text-primary-200 dark:hover:bg-primary-900/30"
                    :disabled="unreadCount === 0 || markingAll"
                    @click="markAllAsRead()"
                >
                    Tout marquer lu
                </button>
            </div>

            <div class="max-h-[28rem] overflow-y-auto">
                <template x-if="loading && notifications.length === 0">
                    <div class="space-y-3 p-4">
                        <div class="h-14 rounded-lg skeleton-shimmer"></div>
                        <div class="h-14 rounded-lg skeleton-shimmer"></div>
                    </div>
                </template>

                <template x-if="!loading && notifications.length === 0">
                    <div class="p-6 text-center">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                            <i data-lucide="bell" class="h-5 w-5" aria-hidden="true"></i>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Aucune notification</p>
                    </div>
                </template>

                <template x-for="notification in notifications" :key="notification.id">
                    <button
                        type="button"
                        class="flex w-full gap-3 border-b border-gray-100 px-4 py-3 text-left transition last:border-b-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/60"
                        :class="notification.is_read ? '' : 'bg-primary-50/60 dark:bg-primary-900/20'"
                        @click="openNotification(notification)"
                    >
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg" :class="iconClass(notification)">
                            <i :data-lucide="notification.icon || 'bell'" class="h-4 w-4" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-start justify-between gap-2">
                                <span class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="notification.title"></span>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" :class="badgeClass(notification)" x-text="notification.status"></span>
                            </span>
                            <span class="mt-1 line-clamp-2 text-xs text-gray-600 dark:text-gray-300" x-text="notification.description"></span>
                            <span class="mt-2 flex items-center justify-between gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                                <span x-text="notification.time_ago || notification.created_at_label"></span>
                                <span x-show="!notification.is_read" class="h-2 w-2 rounded-full bg-primary-600"></span>
                            </span>
                        </span>
                    </button>
                </template>
            </div>

            <a
                href="{{ route('notifications.index') }}"
                class="flex items-center justify-center gap-2 border-t border-gray-100 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                <i data-lucide="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
                Historique complet
            </a>
        </div>
    </div>
@endauth
