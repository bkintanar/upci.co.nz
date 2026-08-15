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
        redirect: '/departments'
    },
    {
        path: '/departments',
        name: 'Departments',
        component: () => import('../views/GetInvolved.vue')
    },
    {
        path: '/departments/:slug',
        name: 'Department',
        component: () => import('../views/Department.vue')
    },
    {
        path: '/apostolic-bible-college/principals-corner',
        name: 'PrincipalsCorner',
        component: () => import('../views/CmsPage.vue')
    },
    {
        path: '/apostolic-bible-college/enrollment',
        name: 'Enrollment',
        component: () => import('../views/CmsPage.vue')
    },
    {
        path: '/find-church',
        name: 'ChurchLocator',
        component: () => import('../views/ChurchLocator.vue')
    },
    {
        path: '/events',
        name: 'Events',
        component: () => import('../views/Events.vue')
    },
    {
        path: '/calendar',
        name: 'Calendar',
        component: () => import('../views/Calendar.vue')
    },
    {
        path: '/ags-updates',
        name: 'AgsUpdates',
        component: () => import('../views/AgsUpdates.vue')
    },
    {
        path: '/connect-with-us',
        name: 'ConnectWithUs',
        component: () => import('../views/ConnectWithUs.vue')
    },
    {
        path: '/cms/:slug(.*)',
        name: 'CmsPage',
        component: () => import('../views/CmsPage.vue')
    },
    // Catch-all: treat any other path as a CMS page slug (e.g. /abc -> page "abc")
    {
        path: '/:slug(.*)',
        name: 'CmsPageBySlug',
        component: () => import('../views/CmsPage.vue')
    }
]

export default routes
