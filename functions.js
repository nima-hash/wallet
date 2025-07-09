// Sanitize input by removing < and > characters
function sanitizeInput(input) {
    if (input === "" || input === undefined) {
        return null
    } 
    return input.trim().replace(/[<>]/g, '');
}



function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function toggleFilters() {
    const filterSection = document.getElementById("filterSection");
    filterSection.style.display = filterSection.style.display === "none" ? "block" : "none";
}

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
//     return response;       
// }
const runFetch = async (url, requestOptions) => {

    try {
        const response = await fetch(url, requestOptions); 
        return response
    } catch (error) {
        throw error;
}
}
function validatePassword(password) {
    // Minimum 8 characters
    const minLength = 8;

    // Regular expressions for validation
    const hasUppercase = /[A-Z]/.test(password); // At least 1 uppercase letter
    const hasLowercase = /[a-z]/.test(password); // At least 1 lowercase letter
    const hasSpecialChar = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password); // At least 1 special character

    // Check all conditions
    if (password.length < minLength) {
        return "Password must be at least 8 characters long.";
    }
    if (!hasUppercase) {
        return "Password must contain at least 1 uppercase letter.";
    }
    if (!hasLowercase) {
        return "Password must contain at least 1 lowercase letter.";
    }
    if (!hasSpecialChar) {
        return "Password must contain at least 1 special character.";
    }

    // If all conditions are met
    return null; // No error
}

