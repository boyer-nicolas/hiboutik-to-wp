import React from 'react';
import Dashboard from '../pages/dashboard';
import Settings from '../pages/settings';
import Search from '../pages/search';

class PageHandler extends React.Component
{
    constructor(props)
    {
        super(props);
        let wp_slug = window.location.search.substr(1)
        let page = wp_slug.split('=')[1];

        this.state = {
            page: page
        }
    }

    // componentDidMount()
    // {
    //     const navLinks = document.querySelectorAll('.nwh-nav-link');

    //     navLinks.forEach(link =>
    //     {
    //         const href = link.getAttribute('href').split('=')[1];;
    //         if (href === this.state.page)
    //         {
    //             navLinks.forEach(link =>
    //             {
    //                 link.classList.remove('active');
    //             });

    //             link.classList.add('active');
    //             // set page title
    //             document.title = link.innerText + " - NiwHiboutik";
    //         }
    //     });

    //     navLinks.forEach(link =>
    //     {
    //         link.addEventListener('click', (e) =>
    //         {
    //             e.preventDefault();

    //             navLinks.forEach(link =>
    //             {
    //                 link.classList.remove('active');
    //             });

    //             e.target.classList.add('active');

    //             let href = link.getAttribute('href').split('=')[1];;
    //             if (!href.includes(this.state.page))
    //             {
    //                 this.setState({
    //                     page: href
    //                 });

    //             }
    //             window.history.pushState(null, null, '?page=' + href);
    //             // set page title
    //             document.title = link.innerText + " - NiwHiboutik";

    //             const wpLinks = document.querySelectorAll('#toplevel_page_niwhiboutik-dashboard a');
    //             wpLinks.forEach(link =>
    //             {
    //                 link.parentNode.classList.remove('current');
    //                 if (link.getAttribute('href').includes(href))
    //                 {
    //                     link.parentNode.classList.add('current');
    //                 }
    //             });
    //         });
    //     });

    //     const wpNavLinks = document.querySelectorAll('#toplevel_page_niwhiboutik-dashboard a');
    //     wpNavLinks.forEach(link =>
    //     {
    //         link.addEventListener('click', (e) =>
    //         {
    //             e.preventDefault();

    //             wpNavLinks.forEach(manyLinks =>
    //             {
    //                 manyLinks.parentNode.classList.remove('current');
    //             });

    //             e.target.parentNode.classList.add('current');

    //             let href = link.getAttribute('href').split('=')[1];;
    //             if (!href.includes(this.state.page))
    //             {
    //                 this.setState({
    //                     page: href
    //                 });
    //                 window.history.pushState(null, null, '?page=' + href);
    //                 document.title = link.innerText + " - NiwHiboutik";

    //                 const links = document.querySelectorAll('.nwh-nav-link');
    //                 links.forEach(link =>
    //                 {
    //                     link.classList.remove('active');
    //                     if (link.getAttribute('href').includes(href))
    //                     {
    //                         link.classList.add('active');
    //                     }
    //                 });
    //             }

    //         });
    //     });

    //     const navBarBrand = document.querySelector('.navbar-brand');
    //     navBarBrand.addEventListener('click', (e) =>
    //     {
    //         e.preventDefault();
    //         this.setState({
    //             page: 'niwhiboutik-dashboard'
    //         });
    //         window.history.pushState(null, null, '?page=niwhiboutik-dashboard');
    //         document.title = "Panneau de Contrôle - NiwHiboutik";

    //         const links = document.querySelectorAll('.nwh-nav-link');
    //         links.forEach(link =>
    //         {
    //             link.classList.remove('active');
    //             if (link.getAttribute('href').includes('niwhiboutik-dashboard'))
    //             {
    //                 link.classList.add('active');
    //             }
    //         });

    //         const wpLinks = document.querySelectorAll('#toplevel_page_niwhiboutik-dashboard a');
    //         wpLinks.forEach(link =>
    //         {
    //             link.parentNode.classList.remove('current');
    //             if (link.getAttribute('href').includes('niwhiboutik-dashboard'))
    //             {
    //                 link.parentNode.classList.add('current');
    //             }
    //         });
    //     })
    // }

    loadPage()
    {
        switch (this.state.page)
        {
            case 'niwhiboutik-dashboard':
                return <Dashboard />;
            case 'niwhiboutik-search':
                return <Search />;
            case 'niwhiboutik-settings':
                return <Settings />;
            default:
                return <Dashboard />;
        }
    }

    render()
    {
        return (
            this.loadPage()
        )
    }
}

export default PageHandler;