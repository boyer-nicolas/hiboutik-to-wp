import React from "react";
import * as BS from "react-bootstrap";
import * as Icon from 'react-feather';
import CockpitCheck from '../lib/CockpitCheck';
import Bootstrap from 'bootstrap';
import Logo from "../img/logo.svg";

class Header extends React.Component
{
    constructor(props)
    {
        super(props);

        const checks = new CockpitCheck();
        this.wp = checks.get_wp_api();

        this.state = {
            import_notice: (
                <div className="d-flex align-items-center justify-content-between">
                    <BS.Spinner
                        className="mx-auto"
                        as="div"
                        animation="border"
                        size="lg"
                        role="status"
                        aria-hidden="true"
                    />
                    <p>{niwhiboutik_po.notifications.loading}...</p>
                </div>
            ),
            schedule: (
                <div className="d-flex align-items-center justify-content-between">
                    <BS.Spinner
                        className="mx-auto"
                        as="div"
                        animation="border"
                        size="lg"
                        role="status"
                        aria-hidden="true"
                    />
                    <p>{niwhiboutik_po.notifications.loading}...</p>
                </div>
            ),
            is_activated: false,
        }

        this.wp.get('/is-activated').then(response =>
        {
            this.setState({
                is_activated: response.data
            });
        }).catch(error =>
        {
            console.error(error);
        });

        this.check_scron_schedule();
        this.check_success_notice();
        this.check_error_notice();
        this.check_stop_notice();

        this.toggleMenuOpened = this.toggleMenuOpened.bind(this);
    }

    toggleMenuOpened(e)
    {
        let el = e.target;

        if (el.parentNode.nodeName === "LABEL")
        {
            el = el.parentNode;
        }

        if (el.classList.contains('active'))
        {
            el.classList.remove('active');
        }
        else
        {
            el.classList.add('active');
        }
    }

    check_scron_schedule()
    {
        this.wp.get('/get-cron-schedule').then(response =>
        {
            if (response.data !== "" && response.data !== false)
            {
                this.setState({
                    schedule: (
                        <BS.Alert className="text-normal" variant="info"><Icon.Info />{response.data}</BS.Alert>
                    )
                });
            }
            else if (response.data !== "" && response.data === false)
            {
                this.setState({
                    schedule: (
                        <BS.Alert className="text-normal" variant="danger"><Icon.XCircle />{niwhiboutik_po.notifications.no_import_registered}</BS.Alert>
                    )
                });
            }
        }
        ).catch(error =>
        {
            console.error(error);
        });
    }

    check_success_notice()
    {
        this.wp.get('/check-success-notice').then(response =>
        {
            if (response.data !== "" && response.data !== false)
            {
                this.setState({
                    import_notice: (
                        <BS.Alert className="text-normal" variant="success"><Icon.CheckCircle />{response.data}</BS.Alert>
                    )
                });
            }
        }
        ).catch(error =>
        {
            console.error(error);
        });
    }

    check_error_notice()
    {
        this.wp.get('/check-error-notice').then(response =>
        {
            if (response.data !== "" && response.data !== false)
            {
                this.setState({
                    import_notice: (
                        <BS.Alert className="text-normal" variant="danger"><Icon.XCircle />{response.data}</BS.Alert>
                    )
                });
            }
        }
        ).catch(error =>
        {
            console.error(error);
        });
    }

    check_stop_notice()
    {
        this.wp.get('/check-stop-notice').then(response =>
        {
            if (response.data !== "" && response.data !== false)
            {
                this.setState({
                    import_notice: (
                        <BS.Alert className="text-normal" variant="danger"><Icon.XCircle />{response.data}</BS.Alert>
                    )
                });
            }
        }
        ).catch(error =>
        {
            console.error(error);
        });
    }

    render()
    {
        return (
            <>
                <BS.Container className="pt-5">
                    <BS.Row>
                        <BS.Col>
                            <a className="nav-link text-dark position-relative p-1 me-2 d-inline-block" href="#" id="notification-dropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span className="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    2
                                    <span className="visually-hidden">{niwhiboutik_po.notifications.unread}</span>
                                </span>
                                <Icon.Bell />
                            </a>
                            <ul className="dropdown-menu" aria-labelledby="notification-dropdown">
                                <li className="head text-light bg-dark">
                                    <div className="row">
                                        <div className="col-lg-12 col-sm-12 col-12 text-center">
                                            <span>{niwhiboutik_po.notifications.title} (2)</span>
                                        </div>
                                    </div>
                                </li>
                                {this.state.import_notice !== ''
                                    ?
                                    <li className="notification-box">
                                        <div className="row">
                                            <div className="p-2 col-lg-10 col-sm-10 col-10 mx-auto">
                                                {this.state.import_notice}
                                            </div>
                                        </div>
                                    </li>
                                    :
                                    null
                                }
                                {this.state.schedule !== ''
                                    ?
                                    <li className="notification-box bg-gray">
                                        <div className="row">
                                            <div className="p-2 col-lg-10 col-sm-10 col-10 mx-auto">
                                                {this.state.schedule}
                                            </div>
                                        </div>
                                    </li>
                                    :
                                    null
                                }
                            </ul>
                        </BS.Col>
                    </BS.Row>
                </BS.Container>
                <BS.Navbar className="mb-3" expand="lg">
                    <BS.Container>
                        <BS.Navbar.Brand className="fw-bold" href="/wp-admin/admin.php?page=niwhiboutik-dashboard">
                            <Logo width="300px" />
                        </BS.Navbar.Brand>
                        <label className="navbar-toggler-nwh" onClick={this.toggleMenuOpened} data-bs-toggle="collapse" data-bs-target="#notifications-navbar-nav" aria-expanded="false" aria-label="Toggle navigation" aria-controls="notifications-navbar-nav" >
                            <span className="navbar-toggle-bar"></span>
                            <span className="navbar-toggle-bar"></span>
                            <span className="navbar-toggle-bar"></span>
                        </label>
                        <BS.Navbar.Collapse id="notifications-navbar-nav" className="justify-content-end">
                            <BS.Nav>
                                {
                                    this.state.is_activated
                                        ?
                                        <>
                                            <BS.Nav.Link className="nwh-nav-link" href="/wp-admin/admin.php?page=niwhiboutik-dashboard">{niwhiboutik_po.menu.control_panel}</BS.Nav.Link>
                                            <BS.Nav.Link className="nwh-nav-link" href="/wp-admin/admin.php?page=niwhiboutik-search">{niwhiboutik_po.menu.search}</BS.Nav.Link>
                                        </>
                                        : null}
                                <BS.Nav.Link className="nwh-nav-link" href="/wp-admin/admin.php?page=niwhiboutik-settings">{niwhiboutik_po.menu.settings}</BS.Nav.Link>
                            </BS.Nav>
                        </BS.Navbar.Collapse>
                    </BS.Container>
                </BS.Navbar>
            </>
        )
    }
}

export default Header;