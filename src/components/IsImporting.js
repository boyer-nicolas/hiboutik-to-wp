import CockpitCheck from '../lib/CockpitCheck';

class IsImporting
{
    constructor()
    {
        const checks = new CockpitCheck();
        this.wp = checks.get_wp_api();

        this.check();
    }

    // Test Hiboutik API
    check()
    {
        return new Promise((resolve, reject) =>
        {
            this.wp.get('/is-importing').then(response =>
            {
                resolve(response.data);
            }).catch(error =>
            {
                reject(error);
            })
        });
    }
}

export default IsImporting;