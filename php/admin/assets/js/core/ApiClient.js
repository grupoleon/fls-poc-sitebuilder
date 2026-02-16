/**
 * ApiClient - Handles all AJAX requests to the backend
 */
class ApiClient {
    constructor(baseUrl='') {
        this.baseUrl=baseUrl;
    }

    /**
     * Make API request
     */
    async request(action,data=null,method='POST') {
        const url=this.baseUrl+(this.baseUrl.includes('?')? '&':'?')+'action='+action;

        const options={
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if(data&&method!=='GET') {
            options.body=JSON.stringify({action,...data});
        }

        window.logger.debug(`API Request: ${method} ${action}`,data);

        try {
            const response=await fetch(url,options);
            const result=await response.json();

            window.logger.debug(`API Response: ${action}`,result);

            if(!result.success) {
                throw new Error(result.error||result.message||'Unknown error');
            }

            return result;
        } catch(error) {
            window.logger.error(`API Error: ${action} - ${error.message}`);
            throw error;
        }
    }

    /**
     * GET request
     */
    async get(action,params={}) {
        const queryString=new URLSearchParams(params).toString();
        const url=this.baseUrl+(this.baseUrl.includes('?')? '&':'?')+
            'action='+action+(queryString? '&'+queryString:'');

        window.logger.debug(`API GET Request: ${action}`,params);

        try {
            const response=await fetch(url,{
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result=await response.json();
            window.logger.debug(`API GET Response: ${action}`,result);

            if(!result.success) {
                throw new Error(result.error||result.message||'Unknown error');
            }

            return result;
        } catch(error) {
            window.logger.error(`API GET Error: ${action} - ${error.message}`);
            throw error;
        }
    }

    /**
     * POST request
     */
    async post(action,data={}) {
        return this.request(action,data,'POST');
    }

    /**
     * Upload file
     */
    async upload(action,formData) {
        const url=this.baseUrl+(this.baseUrl.includes('?')? '&':'?')+'action='+action;

        window.logger.debug(`API Upload: ${action}`);

        try {
            const response=await fetch(url,{
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const result=await response.json();
            window.logger.debug(`API Upload Response: ${action}`,result);

            if(!result.success) {
                throw new Error(result.error||result.message||'Unknown error');
            }

            return result;
        } catch(error) {
            window.logger.error(`API Upload Error: ${action} - ${error.message}`);
            throw error;
        }
    }
}

// Create global instance
window.apiClient=new ApiClient('');
