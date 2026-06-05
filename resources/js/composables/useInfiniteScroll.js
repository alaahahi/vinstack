import { onUnmounted, ref, watchEffect } from 'vue';

/**
 * @param {object} options
 * @param {import('vue').Ref<boolean>} options.enabled
 * @param {import('vue').Ref<boolean>} options.hasMore
 * @param {import('vue').Ref<boolean>} options.loading
 * @param {() => void | Promise<void>} options.onLoadMore
 */
export function useInfiniteScroll({ enabled, hasMore, loading, onLoadMore }) {
    const sentinel = ref(null);
    let observer = null;

    function disconnect() {
        observer?.disconnect();
        observer = null;
    }

    watchEffect((onCleanup) => {
        disconnect();

        if (! enabled.value || ! sentinel.value) {
            return;
        }

        observer = new IntersectionObserver(
            (entries) => {
                const entry = entries[0];

                if (! entry?.isIntersecting || loading.value || ! hasMore.value) {
                    return;
                }

                onLoadMore();
            },
            { root: null, rootMargin: '240px 0px', threshold: 0 },
        );

        observer.observe(sentinel.value);
        onCleanup(disconnect);
    });

    onUnmounted(disconnect);

    return { sentinel };
}
