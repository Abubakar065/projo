/**
 * JavaScript Functions for Project Tracking and Reporting Application
 * Lightweight vanilla JavaScript - no external dependencies
 */

// Declare bootstrap variable if not already defined
var bootstrap = window.bootstrap

document.addEventListener("DOMContentLoaded", () => {
  // Initialize tooltips if Tabler is loaded
  if (typeof bootstrap !== "undefined") {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))
  }

  // Auto-hide alerts after 5 seconds
  const alerts = document.querySelectorAll(".alert:not(.alert-permanent)")
  alerts.forEach((alert) => {
    setTimeout(() => {
      if (alert.parentNode) {
        alert.style.transition = "opacity 0.5s ease-out"
        alert.style.opacity = "0"
        setTimeout(() => {
          if (alert.parentNode) {
            alert.remove()
          }
        }, 500)
      }
    }, 5000)
  })

  // Form validation enhancement
  const forms = document.querySelectorAll('form[data-validate="true"]')
  forms.forEach((form) => {
    form.addEventListener("submit", (e) => {
      if (!validateForm(form)) {
        e.preventDefault()
        e.stopPropagation()
      }
      form.classList.add("was-validated")
    })
  })

  // Photo gallery click handler
  const photoItems = document.querySelectorAll(".photo-item img")
  photoItems.forEach((img) => {
    img.addEventListener("click", function () {
      openPhotoModal(this.src, this.alt)
    })
  })

  // Progress bar animation
  animateProgressBars()

  // Confirm delete actions
  const deleteButtons = document.querySelectorAll("[data-confirm-delete]")
  deleteButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      const message = this.getAttribute("data-confirm-delete") || "Are you sure you want to delete this item?"
      if (!confirm(message)) {
        e.preventDefault()
      }
    })
  })
})

/**
 * Validate form fields
 */
function validateForm(form) {
  let isValid = true

  // Check required fields
  const requiredFields = form.querySelectorAll("[required]")
  requiredFields.forEach((field) => {
    if (!field.value.trim()) {
      isValid = false
      showFieldError(field, "This field is required")
    } else {
      clearFieldError(field)
    }
  })

  // Validate email fields
  const emailFields = form.querySelectorAll('input[type="email"]')
  emailFields.forEach((field) => {
    if (field.value && !isValidEmail(field.value)) {
      isValid = false
      showFieldError(field, "Please enter a valid email address")
    }
  })

  // Validate password confirmation
  const passwordField = form.querySelector('input[name="password"]')
  const confirmPasswordField = form.querySelector('input[name="confirm_password"]')
  if (passwordField && confirmPasswordField) {
    if (passwordField.value !== confirmPasswordField.value) {
      isValid = false
      showFieldError(confirmPasswordField, "Passwords do not match")
    }
  }

  return isValid
}

/**
 * Show field error
 */
function showFieldError(field, message) {
  clearFieldError(field)

  field.classList.add("is-invalid")

  const errorDiv = document.createElement("div")
  errorDiv.className = "invalid-feedback"
  errorDiv.textContent = message

  field.parentNode.appendChild(errorDiv)
}

/**
 * Clear field error
 */
function clearFieldError(field) {
  field.classList.remove("is-invalid")

  const errorDiv = field.parentNode.querySelector(".invalid-feedback")
  if (errorDiv) {
    errorDiv.remove()
  }
}

/**
 * Validate email format
 */
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

/**
 * Animate progress bars on page load
 */
function animateProgressBars() {
  const progressBars = document.querySelectorAll(".progress-bar")
  progressBars.forEach((bar) => {
    const width = bar.style.width || bar.getAttribute("aria-valuenow") + "%"
    bar.style.width = "0%"

    setTimeout(() => {
      bar.style.width = width
    }, 100)
  })
}

/**
 * Open photo in modal (placeholder for future implementation)
 */
function openPhotoModal(src, caption) {
  // For now, just open in new window
  // In future versions, this could open a proper modal
  window.open(src, "_blank")
}

/**
 * Format currency
 */
function formatCurrency(amount, currency = "NGN") {
  const formatter = new Intl.NumberFormat("en-NG", {
    style: "currency",
    currency: currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  })

  return formatter.format(amount)
}

/**
 * Format percentage
 */
function formatPercentage(value, decimals = 1) {
  return Number.parseFloat(value).toFixed(decimals) + "%"
}

/**
 * Show loading state on form submission
 */
function showFormLoading(form) {
  const submitButton = form.querySelector('button[type="submit"]')
  if (submitButton) {
    submitButton.disabled = true
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...'
  }

  form.classList.add("loading")
}

/**
 * Hide loading state
 */
function hideFormLoading(form) {
  const submitButton = form.querySelector('button[type="submit"]')
  if (submitButton) {
    submitButton.disabled = false
    // Restore original button text (should be stored in data attribute)
    const originalText = submitButton.getAttribute("data-original-text") || "Submit"
    submitButton.innerHTML = originalText
  }

  form.classList.remove("loading")
}

/**
 * AJAX helper function
 */
function makeRequest(url, options = {}) {
  const defaults = {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  }

  const config = Object.assign(defaults, options)

  return fetch(url, config)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok")
      }
      return response.json()
    })
    .catch((error) => {
      console.error("Request failed:", error)
      throw error
    })
}

/**
 * Show notification (placeholder for future toast notifications)
 */
function showNotification(message, type = "info") {
  // For now, just use alert
  // In future versions, this could show toast notifications
  alert(message)
}
