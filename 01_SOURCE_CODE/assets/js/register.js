/* ==========================================================
        REGISTER.JS - LRN PURIFIED DRINKING WATER
   ========================================================== */

document.addEventListener("DOMContentLoaded", function () {
    const registrationForm = document.getElementById("registrationForm");
    const summaryCard = document.getElementById("summaryCard");
    const summaryContent = document.getElementById("summaryContent");
    const btnReset = document.getElementById("btnReset");

    const birthDateInput = document.getElementById("birthDate");
    const ageFeedback = document.getElementById("ageFeedback");

    const passwordInput = document.getElementById("password");
    const confirmPasswordInput = document.getElementById("confirmPassword");
    const strengthContainer = document.getElementById("passwordStrengthContainer");
    const strengthBar = document.getElementById("strengthBar");
    const strengthText = document.getElementById("strengthText");

    // ==========================================
    // HELPER: CALCULATE EXACT AGE
    // ==========================================
    function getExactAge(birthDateValue) {
        if (!birthDateValue) return null;
        const today = new Date();
        const birthDate = new Date(birthDateValue);

        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age;
    }

    // ==========================================
    // 1. AGE VERIFICATION (18+)
    // ==========================================
    function verifyAge() {
        const birthDateValue = birthDateInput.value;

        if (!birthDateValue) {
            birthDateInput.classList.remove("valid-age", "invalid-age");
            ageFeedback.className = "age-feedback";
            ageFeedback.textContent = "";
            return false;
        }

        const today = new Date();
        const birthDate = new Date(birthDateValue);

        if (birthDate > today) {
            birthDateInput.classList.remove("valid-age");
            birthDateInput.classList.add("invalid-age");
            ageFeedback.className = "age-feedback ineligible";
            ageFeedback.innerHTML = "&#10006; Invalid date: Birthdate cannot be in the future.";
            return false;
        }

        const age = getExactAge(birthDateValue);

        if (age >= 18) {
            birthDateInput.classList.remove("invalid-age");
            birthDateInput.classList.add("valid-age");
            ageFeedback.className = "age-feedback eligible";
            ageFeedback.innerHTML = `&#10004; Eligible: ${age} years old (18+ verified)`;
            return true;
        } else {
            birthDateInput.classList.remove("valid-age");
            birthDateInput.classList.add("invalid-age");
            ageFeedback.className = "age-feedback ineligible";
            ageFeedback.innerHTML = `&#10006; Ineligible: ${age} years old. You must be at least 18 years old to register.`;
            return false;
        }
    }

    if (birthDateInput) {
        birthDateInput.addEventListener("change", verifyAge);
        birthDateInput.addEventListener("input", verifyAge);
    }

    // ==========================================
    // 2. PASSWORD STRENGTH CHECKER
    // ==========================================
    function evaluatePassword(pwd) {
        if (!pwd) return "";

        let score = 0;
        const hasLength = pwd.length >= 8;
        const hasLower = /[a-z]/.test(pwd);
        const hasUpper = /[A-Z]/.test(pwd);
        const hasNumber = /[0-9]/.test(pwd);
        const hasSpecial = /[^A-Za-z0-9]/.test(pwd);

        if (hasLength) score++;
        if (hasLower) score++;
        if (hasUpper) score++;
        if (hasNumber) score++;
        if (hasSpecial) score++;

        if (hasLength && hasLower && hasUpper && hasNumber && hasSpecial) {
            return "strong";
        }
        if (hasLength && score >= 3) {
            return "moderate";
        }
        return "weak";
    }

    function updatePasswordMeter() {
        const pwd = passwordInput.value;
        const strength = evaluatePassword(pwd);

        strengthContainer.classList.remove("strength-weak", "strength-moderate", "strength-strong");

        if (!pwd) {
            strengthContainer.style.display = "none";
            return;
        }

        strengthContainer.style.display = "flex";

        if (strength === "weak") {
            strengthContainer.classList.add("strength-weak");
            strengthText.textContent = "Strength: Weak (Too simple)";
        } else if (strength === "moderate") {
            strengthContainer.classList.add("strength-moderate");
            strengthText.textContent = "Strength: Moderate (Add symbols/casing)";
        } else if (strength === "strong") {
            strengthContainer.classList.add("strength-strong");
            strengthText.textContent = "Strength: Strong ✔ (Accepted)";
        }
    }

    if (passwordInput) {
        passwordInput.addEventListener("input", updatePasswordMeter);
    }

    // ==========================================
    // 3. USERNAME GENERATOR (Rules 1 to 8)
    // ==========================================
    function generateUsername(firstName, lastName, birthDateStr, email, age) {
        const firstPart = firstName.slice(0, 3);
        const lastPart = lastName.slice(0, 3);
        const birthYear = birthDateStr.split("-")[0];
        const emailPrefix = email.split("@")[0] || "";
        const emailPart = emailPrefix.slice(0, 2);

        return `${firstPart}${lastPart}${birthYear}_${emailPart}${age}`.toLowerCase();
    }

    // ==========================================
    // 4. RESET BUTTON
    // ==========================================
    if (btnReset) {
        btnReset.addEventListener("click", function () {
            if (summaryCard) summaryCard.style.display = "none";
            if (birthDateInput) birthDateInput.classList.remove("valid-age", "invalid-age");
            if (ageFeedback) {
                ageFeedback.className = "age-feedback";
                ageFeedback.textContent = "";
            }
            if (strengthContainer) {
                strengthContainer.style.display = "none";
                strengthContainer.classList.remove("strength-weak", "strength-moderate", "strength-strong");
            }
        });
    }

    // ==========================================
    // 5. FORM SUBMISSION
    // ==========================================
    if (registrationForm) {
        registrationForm.addEventListener("submit", function (e) {

            // 1. Guard: Age Requirement (18+)
            const isAgeEligible = verifyAge();
            if (!isAgeEligible) {
                e.preventDefault(); // STOP submission
                alert("Registration rejected: Applicant must be at least 18 years old.");
                birthDateInput.focus();
                return;
            }

            // 2. Guard: Only Accept Strong Passwords
            const password = passwordInput.value;
            const passwordStrength = evaluatePassword(password);
            if (passwordStrength !== "strong") {
                e.preventDefault(); // STOP submission
                alert("Password requirement not met: Your password must be rated 'Strong'.");
                passwordInput.focus();
                return;
            }

            // 3. Guard: Password Match
            const confirmPassword = confirmPasswordInput.value;
            if (password !== confirmPassword) {
                e.preventDefault(); // STOP submission
                alert("Passwords do not match. Please re-enter.");
                confirmPasswordInput.focus();
                return;
            }

            // 4. Retrieve Customer Details
            const firstName = document.getElementById("firstName").value.trim();
            const middleInitial = document.getElementById("middleInitial").value.trim();
            const lastName = document.getElementById("lastName").value.trim();
            const suffix = document.getElementById("suffix").value.trim();

            let formattedFullName = firstName;
            if (middleInitial) {
                const mi = middleInitial.endsWith(".") ? middleInitial : `${middleInitial}.`;
                formattedFullName += ` ${mi}`;
            }
            formattedFullName += ` ${lastName}`;
            if (suffix) {
                formattedFullName += ` ${suffix}`;
            }

            const customerId = document.getElementById("customerId").value.trim();
            const contactNumber = document.getElementById("contactNumber").value.trim();
            const birthDate = birthDateInput.value;
            const email = document.getElementById("email").value.trim();
            const customerType = document.getElementById("customerType").value;
            const deliveryAddress = document.getElementById("address").value.trim();
            const termsAccepted = document.getElementById("terms").checked ? "Yes" : "No";

            // 5. Generate Username & Age
            const userAge = getExactAge(birthDate);
            const generatedUsername = generateUsername(firstName, lastName, birthDate, email, userAge);

            // 6. Render Summary Card with Water Station Labels
            summaryContent.innerHTML = `
                <table class="summary-table">
                    <tr class="username-highlight-row">
                        <th>Generated Username</th>
                        <td><span class="username-badge">${generatedUsername}</span></td>
                    </tr>
                    <tr>
                        <th>Customer Name</th>
                        <td>${formattedFullName}</td>
                    </tr>
                    <tr>
                        <th>Customer / Account ID</th>
                        <td>${customerId}</td>
                    </tr>
                    <tr>
                        <th>Contact Number</th>
                        <td>${contactNumber}</td>
                    </tr>
                    <tr>
                        <th>Birth Date</th>
                        <td>${birthDate} <span style="color: #15803d; font-weight: bold;">(Age: ${userAge}, Verified 18+)</span></td>
                    </tr>
                    <tr>
                        <th>Email Address</th>
                        <td>${email}</td>
                    </tr>
                    <tr>
                        <th>Customer Classification</th>
                        <td>${customerType}</td>
                    </tr>
                    <tr>
                        <th>Delivery Address & Notes</th>
                        <td>${deliveryAddress}</td>
                    </tr>
                    <tr>
                        <th>Terms & Policy Accepted</th>
                        <td>${termsAccepted}</td>
                    </tr>
                </table>
            `;

            summaryCard.style.display = "block";
            summaryCard.scrollIntoView({ behavior: "smooth" });
        });
    }
});