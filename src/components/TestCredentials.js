import Axios from "axios";
import CockpitCheck from '../lib/CockpitCheck';

class TestCredentials
{
    constructor()
    {
        const checks = new CockpitCheck();
        this.wp = checks.get_wp_api();

        this.testHiboutik();
    }

    // Get Hiboutik Auth Info (if there is any)
    getHiboutikInfo()
    {
        return new Promise((resolve, reject) =>
        {
            this.wp.get('/get-hiboutik-params').then(response =>
            {
                resolve(response.data.data);

            }).catch(error =>
            {
                console.error(error);
                reject(error);
            })
        });
    }

    // Test Hiboutik API
    testHiboutik()
    {
        return new Promise((resolve) =>
        {
            this.getHiboutikInfo().then(response =>
            {
                if (response)
                {
                    let hiboutik_link = response.hiboutik_url,
                        hiboutik_login = response.hiboutik_login,
                        hiboutik_key = response.hiboutik_key;

                    setTimeout(() =>
                    {
                        if (hiboutik_link !== "" && hiboutik_login !== "" && hiboutik_key !== "")
                        {
                            Axios.get(hiboutik_link + "/brands", {
                                auth: {
                                    username: hiboutik_login,
                                    password: hiboutik_key
                                }
                            }).then(response =>
                            {
                                if (response.status === 200)
                                {
                                    resolve(true);
                                }
                            }).catch(error =>
                            {
                                console.error(error);
                                if (error.response.status === 401)
                                {
                                    resolve(false);
                                }
                                else if (error.response.status === 500)
                                {
                                    resolve(false);
                                }
                                else if (error.response.status === 404)
                                {
                                    resolve(false);
                                }
                                else if (error.response.status === 0 && error.code === "ERR_NETWORK")
                                {

                                    resolve(false);
                                }
                                else
                                {
                                    resolve(false);
                                }
                            });
                        }
                        else
                        {
                            resolve(false);
                        }
                    }, 1000);
                }
                else
                {
                    return false;
                }
            });
        });
    }
}

export default TestCredentials;