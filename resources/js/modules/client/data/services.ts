export interface Service {
    id: string;
    slug: string;
    number: string;
    title: string;
    shortDescription: string;
    category: 'Development' | 'WHMCS' | 'WordPress' | 'Maintenance';
    audience: string;
    scope: string[];
    outcome: string;
}

export const services: Service[] = [
    {
        id: 'web-development',
        slug: 'web-development',
        number: '01',
        title: 'Web development',
        category: 'Development',
        shortDescription:
            'Websites and web applications shaped around how your business works.',
        audience:
            'For businesses starting a new website, improving an existing platform, or building a custom workflow.',
        scope: [
            'Business websites and customer portals',
            'Custom web applications and integrations',
            'Responsive interfaces and usability improvements',
            'Changes to an existing application',
        ],
        outcome:
            'Bring your requirements, a reference, or simply the problem you need to solve. We can discuss the right scope before development begins.',
    },
    {
        id: 'mobile-app-development',
        slug: 'mobile-app-development',
        number: '02',
        title: 'Mobile app development',
        category: 'Development',
        shortDescription:
            'Purposeful mobile experiences that connect your customers and services.',
        audience:
            'For businesses that need a mobile interface for their customers, staff, or an existing online service.',
        scope: [
            'App planning and interface design',
            'Mobile application development',
            'Connections to existing services and APIs',
            'Updates and improvements to an app',
        ],
        outcome:
            'Tell us who will use the app, what they need to do, and which devices you need to support. Platform and delivery requirements are agreed for each project.',
    },
    {
        id: 'whmcs-customisation',
        slug: 'whmcs-customisation',
        number: '03',
        title: 'WHMCS customisation',
        category: 'WHMCS',
        shortDescription:
            'Adapt the client area, billing workflows, and integrations to your operation.',
        audience:
            'For hosting providers and businesses using WHMCS who need functionality beyond the default setup.',
        scope: [
            'Client area changes and interface improvements',
            'Custom hooks and workflow modifications',
            'Connections to other business systems',
            'Troubleshooting existing customisations',
        ],
        outcome:
            'Share your WHMCS version, current setup, and the behaviour you want to change. We review the requirements and compatibility before agreeing the work.',
    },
    {
        id: 'whmcs-modules-templates',
        slug: 'whmcs-modules-templates',
        number: '04',
        title: 'WHMCS modules & templates',
        category: 'WHMCS',
        shortDescription:
            'Extend your platform with focused functionality and a considered client experience.',
        audience:
            'For WHMCS businesses looking for an existing product or a module or template tailored to their needs.',
        scope: [
            'Available modules and templates from our catalog',
            'Custom module development',
            'Template development and modifications',
            'Installation and compatibility troubleshooting',
        ],
        outcome:
            'Browse the catalog for available products and their published license terms. For custom requirements, start with a description of the functionality or interface you need.',
    },
    {
        id: 'wordpress-plugins',
        slug: 'wordpress-plugins',
        number: '05',
        title: 'WordPress plugins',
        category: 'WordPress',
        shortDescription:
            'Add the functionality your WordPress website needs, with a clear purpose.',
        audience:
            'For WordPress site owners who need a specific feature, an integration, or help with an existing plugin.',
        scope: [
            'Available plugins from our product catalog',
            'Custom plugin development',
            'Plugin modification and integrations',
            'Compatibility and functionality fixes',
        ],
        outcome:
            'Describe the feature you need and how it should fit into your site. Include your WordPress version and relevant plugins so we can review the environment.',
    },
    {
        id: 'wordpress-whmcs-management',
        slug: 'wordpress-whmcs-management',
        number: '06',
        title: 'WordPress & WHMCS management',
        category: 'Maintenance',
        shortDescription:
            'Practical care for the platforms that keep your online business running.',
        audience:
            'For businesses that need help maintaining an existing WordPress or WHMCS installation.',
        scope: [
            'Platform updates and maintenance',
            'Configuration and routine administration',
            'Plugin, module, and template checks',
            'Diagnosis of operational issues',
        ],
        outcome:
            'Management is separate from building a new application. The systems covered, access requirements, tasks, and support arrangements are agreed before work begins.',
    },
    {
        id: 'server-management',
        slug: 'server-management',
        number: '07',
        title: 'Server management & maintenance',
        category: 'Maintenance',
        shortDescription:
            'Configuration, upkeep, and hands-on help for your server environment.',
        audience:
            'For businesses that operate servers and need technical help maintaining their environment.',
        scope: [
            'Server setup and configuration',
            'Maintenance and software updates',
            'Performance and configuration investigations',
            'Technical issue diagnosis and resolution',
        ],
        outcome:
            'Tell us about the server, the services it runs, and the work you need. Access and maintenance windows are discussed for the agreed scope.',
    },
    {
        id: 'technical-support',
        slug: 'technical-support',
        number: '08',
        title: 'Troubleshooting & technical support',
        category: 'Maintenance',
        shortDescription:
            'Understand what went wrong and work toward a practical resolution.',
        audience:
            'For businesses facing a website, application, WordPress, WHMCS, or server issue.',
        scope: [
            'Error investigation and diagnosis',
            'Configuration and integration issues',
            'Application and platform troubleshooting',
            'Fixes and guidance for the identified issue',
        ],
        outcome:
            'Describe what happened, when it started, and what changed recently. Existing customers can use a support ticket to keep the conversation with their account.',
    },
];
