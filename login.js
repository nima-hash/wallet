document.addEventListener('DOMContentLoaded', function () {
    const submitButton = document.getElementById('submitLogin');

    const login = async (event) => {
        try {
            event.preventDefault();
            const form = submitButton.closest('form');
            const formData = new FormData(form);
            formData.append('action', 'login');
    
            const sanitizedData = new FormData();
            formData.forEach((value, key) => {
                const cleanValue = sanitizeInput(value);
                sanitizedData.append(key, cleanValue);
            });
    
            const response = await runFetch('login.php', {
                method: 'POST',
                body: sanitizedData,
            });
            
            let result = await response.json(); // Get raw response
            console.log (result)
            if (result.success) {
                sessionStorage.setItem("token", result.token);
                window.location.href = 'index.php'; // Redirect on success
            } else {
                throw new Error(result.error || 'Login failed. Please try again.'); // Show error message
            }
        } catch (error) {
            console.error('Error:', error.message);
        }
        
    }

    submitButton.addEventListener('click', login);

    // const runFetch = async (url, requestOptions) => {
    //     const response = await fetch(url, requestOptions);
    //     if (!response.ok) {
    //         const errorData = await response.json();
    //         let errorText = '';
    //         switch (response.status) {
    //             case 400:
    //                 errorText = 'Bad Request:';
    //                 break;
    //             case 401:
    //                 errorText = 'Unauthorized:';
    //                 break;
    //             case 500:
    //                 errorText = 'Server Error:';
    //                 break;
    //             default:
    //                 errorText = 'Unknown Error:';
    //         }
    //         throw new Error(errorData.error || errorText);
    //     }
    //     return await response.json();       
    // };

    // // Sanitize input by removing < and > characters
    // function sanitizeInput(input) {
    //     return input.trim().replace(/[<>]/g, '');
    // }
});