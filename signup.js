
document.addEventListener ('DOMContentLoaded', function() {
    const submitBtn = document.getElementById('submit_Btn')
    const submitForm = async($e) => {
        $e.preventDefault()
        const form = document.getElementById('signup__form')
        
        //empty Error fields
        const errorDivs = form.querySelectorAll(".invalid-input__err")
        errorDivs.forEach(element => {
            element.innerText = ""
        });
        const formdata = new FormData (form)
        let sanitizedData = new FormData();

        //sanitize and validate
        try {
            const passError = validatePassword(formdata.get('pass__input'))
            if (passError){
                throw {message: passError, parameter: 'pass__input'};
            }
            if (formdata.get('pass__input') !== formdata.get('pass-verify__input')){
                throw {message: "Veryfy password does not match password!!", parameter: 'pass-verify__input'};
            }
            
            formdata.forEach((value, key) => {
                const cleanValue = sanitizeInput(value);
                if (!cleanValue){
                    throw {message: key + " can not be empty!!", parameter: key};
                }
                if (key === "email__input" && !validateEmail(cleanValue)) {
                    throw {message:"Invalid email address", parameter: "email__input"};
                }
                if (key === "phone__input" && !/^\d+$/.test(cleanValue)) {
                    throw {message: "Phone number must contain only digits", parameter: "phone__input"};
                }
                sanitizedData.append(key, cleanValue);
            });

            let response = await runFetch("signup.php", {
                method: "POST",
                body: formdata, // Don't set Content-Type manually
            });

            let result = await response.json(); // Get raw response
            if (result.success) {
                window.location.href = 'login.php'; // Redirect on success
            } else {
                throw new Error(result.error || 'Sign up failed. Please try again.'); // Show error message
            }
        } catch (error){
            if (error.parameter) {
                const id = error.parameter + "__err";
                const errorDiv = document.getElementById(id);
                errorDiv.innerText = error.message;
            }
            console.log(error.message)

        }
    }
    submitBtn.addEventListener('click', submitForm)

    
    // // Removes < and >
    // function sanitizeInput(input) {
    //     return input.trim().replace(/[<>]/g, ""); 
    // }
    
    // function validateEmail(email) {
    //     return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    // }
})