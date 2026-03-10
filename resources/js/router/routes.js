const routes = [
    {
        path: '/',
        name: 'Home',
        component: () => import('../views/CmsPage.vue')
    },
    {
        path: '/about',
        name: 'About',
        component: () => import('../views/About.vue')
    },
    {
        path: '/about/upci',
        name: 'AboutUPCI',
        component: () => import('../views/CmsPage.vue')
    },
    {
        path: '/about/oneness-pentecostalism',
        name: 'OnenessPentecostalism',
        component: () => import('../views/CmsPage.vue')
    },
    {
        path: '/about/beliefs',
        name: 'OurBeliefs',
        component: () => import('../views/CmsPage.vue')
    },
    {
        path: '/about/leadership',
        name: 'Leadership',
        component: () => import('../views/CmsPage.vue')
    },
    {
        path: '/about/general-superintendent',
        name: 'GeneralSuperintendent',
        component: () => import('../views/about/GeneralSuperintendent.vue')
    },
    {
        path: '/get-involved',
        name: 'GetInvolved',
        component: () => import('../views/GetInvolved.vue')
    },
    {
        path: '/find-church',
        name: 'ChurchLocator',
        component: () => import('../views/ChurchLocator.vue')
    },
    {
        path: '/cms/:slug(.*)',
        name: 'CmsPage',
        component: () => import('../views/CmsPage.vue')
    }
]

export default routes
