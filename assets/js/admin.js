// Admin Panel JavaScript - Global News Network
// BACKEND DEVELOPER: This file contains all JavaScript functions for the admin panel

console.log("Admin panel JavaScript loaded")

// Global variables
var sidebar_open = true

// Initialize admin panel when page loads
document.addEventListener("DOMContentLoaded", () => {
  console.log("Admin panel loaded")

  // Check if we're on mobile
  if (window.innerWidth <= 768) {
    sidebar_open = false
    toggleSidebar()
  }

  // Add event listeners
  setupEventListeners()

  // Initialize any tooltips or other UI elements
  initializeUI()
})

// Setup event listeners
function setupEventListeners() {
  // Mobile menu button
  var mobile_btn = document.querySelector(".mobile-menu-btn")
  if (mobile_btn) {
    mobile_btn.addEventListener("click", toggleSidebar)
  }

  // Window resize handler
  window.addEventListener("resize", () => {
    if (window.innerWidth <= 768) {
      if (sidebar_open) {
        sidebar_open = false
        toggleSidebar()
      }
    } else {
      if (!sidebar_open) {
        sidebar_open = true
        toggleSidebar()
      }
    }
  })

  // Form validation
  var forms = document.querySelectorAll("form")
  for (var formIndex = 0; formIndex < forms.length; formIndex++) {
    forms[formIndex].addEventListener("submit", validateForm)
  }

  // Auto-hide alerts after 5 seconds
  var alerts = document.querySelectorAll(".alert")
  for (var alertIndex = 0; alertIndex < alerts.length; alertIndex++) {
    setTimeout(
      ((alert) => () => {
        fadeOut(alert)
      })(alerts[alertIndex]),
      5000,
    )
  }
}

// Toggle sidebar function
function toggleSidebar() {
  var sidebar = document.querySelector(".admin-sidebar")
  var main = document.querySelector(".admin-main")

  if (sidebar && main) {
    if (window.innerWidth <= 768) {
      // Mobile behavior
      sidebar.classList.toggle("show")
    } else {
      // Desktop behavior
      sidebar.classList.toggle("collapsed")
      main.classList.toggle("expanded")
    }

    sidebar_open = !sidebar_open
    console.log("Sidebar toggled:", sidebar_open)
  }
}

// Initialize UI elements
function initializeUI() {
  // Add loading states to buttons
  var buttons = document.querySelectorAll(".btn")
  for (var buttonIndex = 0; buttonIndex < buttons.length; buttonIndex++) {
    buttons[buttonIndex].addEventListener("click", function (e) {
      if (this.type === "submit" || this.classList.contains("loading-btn")) {
        addLoadingState(this)
      }
    })
  }

  // Initialize rich text editors if TinyMCE is available
  var tinymce = window.tinymce // Declare tinymce variable
  if (typeof tinymce !== "undefined") {
    console.log("TinyMCE is available")
  }

  // Initialize file upload previews
  var file_inputs = document.querySelectorAll('input[type="file"]')
  for (var fileInputIndex = 0; fileInputIndex < file_inputs.length; fileInputIndex++) {
    file_inputs[fileInputIndex].addEventListener("change", handleFilePreview)
  }
}

// Add loading state to button
function addLoadingState(button) {
  var original_text = button.innerHTML
  button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...'
  button.disabled = true

  // Remove loading state after 3 seconds (or when form submits)
  setTimeout(() => {
    button.innerHTML = original_text
    button.disabled = false
  }, 3000)
}

// Handle file upload preview
function handleFilePreview(event) {
  var file = event.target.files[0]
  if (file && file.type.startsWith("image/")) {
    var reader = new FileReader()
    reader.onload = (e) => {
      showImagePreview(e.target.result, event.target)
    }
    reader.readAsDataURL(file)
  }
}

// Show image preview
function showImagePreview(src, input) {
  var preview_id = "preview-" + input.id
  var existing_preview = document.getElementById(preview_id)

  if (existing_preview) {
    existing_preview.remove()
  }

  var preview_div = document.createElement("div")
  preview_div.id = preview_id
  preview_div.style.marginTop = "10px"

  var img = document.createElement("img")
  img.src = src
  img.style.maxWidth = "200px"
  img.style.maxHeight = "150px"
  img.style.borderRadius = "5px"
  img.style.border = "1px solid #ddd"

  var label = document.createElement("p")
  label.innerHTML = "<small>Preview:</small>"
  label.style.marginBottom = "5px"

  preview_div.appendChild(label)
  preview_div.appendChild(img)

  input.parentNode.appendChild(preview_div)
}

// Form validation
function validateForm(event) {
  var form = event.target
  var is_valid = true
  var error_messages = []

  // Check required fields
  var required_fields = form.querySelectorAll("[required]")
  for (var requiredFieldIndex = 0; requiredFieldIndex < required_fields.length; requiredFieldIndex++) {
    var field = required_fields[requiredFieldIndex]
    if (!field.value.trim()) {
      is_valid = false
      error_messages.push(field.previousElementSibling.textContent + " is required")
      field.style.borderColor = "#e74c3c"
    } else {
      field.style.borderColor = "#ddd"
    }
  }

  // Email validation
  var email_fields = form.querySelectorAll('input[type="email"]')
  for (var emailFieldIndex = 0; emailFieldIndex < email_fields.length; emailFieldIndex++) {
    var email = email_fields[emailFieldIndex]
    if (email.value && !isValidEmail(email.value)) {
      is_valid = false
      error_messages.push("Please enter a valid email address")
      email.style.borderColor = "#e74c3c"
    }
  }

  // If validation fails, show errors
  if (!is_valid) {
    event.preventDefault()
    showAlert("Please fix the following errors:\n• " + error_messages.join("\n• "), "error")
    return false
  }

  return true
}

// Email validation helper
function isValidEmail(email) {
  var email_regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return email_regex.test(email)
}

// Show alert message
function showAlert(message, type) {
  var alert_div = document.createElement("div")
  alert_div.className = "alert alert-" + (type || "success")

  var icon = type === "error" ? "fas fa-exclamation-circle" : "fas fa-check-circle"
  alert_div.innerHTML = '<i class="' + icon + '"></i> ' + message

  // Insert at top of content area
  var content = document.querySelector(".admin-content")
  if (content) {
    content.insertBefore(alert_div, content.firstChild)

    // Auto-hide after 5 seconds
    setTimeout(() => {
      fadeOut(alert_div)
    }, 5000)
  } else {
    // Fallback to regular alert
    alert(message)
  }
}

// Fade out element
function fadeOut(element) {
  var opacity = 1
  var timer = setInterval(() => {
    if (opacity <= 0.1) {
      clearInterval(timer)
      element.style.display = "none"
      if (element.parentNode) {
        element.parentNode.removeChild(element)
      }
    }
    element.style.opacity = opacity
    opacity -= opacity * 0.1
  }, 50)
}

// Confirm delete function
function confirmDelete(item_name, callback) {
  var message = "Are you sure you want to delete this " + item_name + "? This action cannot be undone."
  if (confirm(message)) {
    if (typeof callback === "function") {
      callback()
    }
    return true
  }
  return false
}

// AJAX helper function
function sendAjaxRequest(url, data, callback) {
  var xhr = new XMLHttpRequest()
  xhr.open("POST", url, true)
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded")

  xhr.onreadystatechange = () => {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        try {
          var response = JSON.parse(xhr.responseText)
          callback(null, response)
        } catch (e) {
          // If not JSON, return as text
          callback(null, xhr.responseText)
        }
      } else {
        callback("Request failed with status: " + xhr.status, null)
      }
    }
  }

  // Convert data object to URL-encoded string
  var form_data = ""
  if (typeof data === "object") {
    var pairs = []
    for (var key in data) {
      if (data.hasOwnProperty(key)) {
        pairs.push(encodeURIComponent(key) + "=" + encodeURIComponent(data[key]))
      }
    }
    form_data = pairs.join("&")
  } else {
    form_data = data
  }

  xhr.send(form_data)
}

// Table sorting function
function sortTable(table_id, column_index, data_type) {
  var table = document.getElementById(table_id)
  if (!table) return

  var tbody = table.querySelector("tbody")
  var rows = Array.from(tbody.querySelectorAll("tr"))

  // Determine sort direction
  var header = table.querySelectorAll("th")[column_index]
  var is_ascending = !header.classList.contains("sort-desc")

  // Remove existing sort classes
  var headers = table.querySelectorAll("th")
  for (var headerIndex = 0; headerIndex < headers.length; headerIndex++) {
    headers[headerIndex].classList.remove("sort-asc", "sort-desc")
  }

  // Add sort class to current header
  header.classList.add(is_ascending ? "sort-asc" : "sort-desc")

  // Sort rows
  rows.sort((a, b) => {
    var a_value = a.cells[column_index].textContent.trim()
    var b_value = b.cells[column_index].textContent.trim()

    if (data_type === "number") {
      a_value = Number.parseFloat(a_value) || 0
      b_value = Number.parseFloat(b_value) || 0
    } else if (data_type === "date") {
      a_value = new Date(a_value)
      b_value = new Date(b_value)
    }

    if (a_value < b_value) return is_ascending ? -1 : 1
    if (a_value > b_value) return is_ascending ? 1 : -1
    return 0
  })

  // Re-append sorted rows
  for (var rowIndex = 0; rowIndex < rows.length; rowIndex++) {
    tbody.appendChild(rows[rowIndex])
  }
}

// Search function for tables
function searchTable(input_id, table_id) {
  var input = document.getElementById(input_id)
  var table = document.getElementById(table_id)

  if (!input || !table) return

  var filter = input.value.toLowerCase()
  var rows = table.querySelectorAll("tbody tr")

  for (var rowIndex = 0; rowIndex < rows.length; rowIndex++) {
    var row = rows[rowIndex]
    var text = row.textContent.toLowerCase()

    if (text.indexOf(filter) > -1) {
      row.style.display = ""
    } else {
      row.style.display = "none"
    }
  }
}

// Auto-save functionality for forms
var auto_save_timer
var auto_save_data = {}

function enableAutoSave(form_id, save_url) {
  var form = document.getElementById(form_id)
  if (!form) return

  var inputs = form.querySelectorAll("input, textarea, select")
  for (var inputIndex = 0; inputIndex < inputs.length; inputIndex++) {
    inputs[inputIndex].addEventListener("input", () => {
      clearTimeout(auto_save_timer)
      auto_save_timer = setTimeout(() => {
        autoSaveForm(form_id, save_url)
      }, 2000) // Auto-save after 2 seconds of inactivity
    })
  }
}

function autoSaveForm(form_id, save_url) {
  var form = document.getElementById(form_id)
  if (!form) return

  var form_data = new FormData(form)
  form_data.append("auto_save", "1")

  // Convert FormData to URL-encoded string
  var data_string = ""
  var pairs = []
  for (var pair of form_data.entries()) {
    pairs.push(encodeURIComponent(pair[0]) + "=" + encodeURIComponent(pair[1]))
  }
  data_string = pairs.join("&")

  sendAjaxRequest(save_url, data_string, (error, response) => {
    if (!error) {
      console.log("Auto-saved successfully")
      showAutoSaveIndicator()
    } else {
      console.log("Auto-save failed:", error)
    }
  })
}

function showAutoSaveIndicator() {
  var indicator = document.getElementById("auto-save-indicator")
  if (!indicator) {
    indicator = document.createElement("div")
    indicator.id = "auto-save-indicator"
    indicator.style.position = "fixed"
    indicator.style.top = "20px"
    indicator.style.right = "20px"
    indicator.style.background = "#2ecc71"
    indicator.style.color = "white"
    indicator.style.padding = "10px 15px"
    indicator.style.borderRadius = "5px"
    indicator.style.fontSize = "0.9em"
    indicator.style.zIndex = "9999"
    indicator.style.opacity = "0"
    indicator.style.transition = "opacity 0.3s ease"
    document.body.appendChild(indicator)
  }

  indicator.innerHTML = '<i class="fas fa-check"></i> Auto-saved'
  indicator.style.opacity = "1"

  setTimeout(() => {
    indicator.style.opacity = "0"
  }, 2000)
}

// Utility functions
function formatNumber(num) {
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
}

function formatDate(date_string) {
  var date = new Date(date_string)
  var options = { year: "numeric", month: "short", day: "numeric" }
  return date.toLocaleDateString("en-US", options)
}

function formatDateTime(date_string) {
  var date = new Date(date_string)
  var options = {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }
  return date.toLocaleDateString("en-US", options)
}

// Export functions for use in other scripts
window.AdminPanel = {
  toggleSidebar: toggleSidebar,
  showAlert: showAlert,
  confirmDelete: confirmDelete,
  sendAjaxRequest: sendAjaxRequest,
  sortTable: sortTable,
  searchTable: searchTable,
  enableAutoSave: enableAutoSave,
  formatNumber: formatNumber,
  formatDate: formatDate,
  formatDateTime: formatDateTime,
}

console.log("Admin panel JavaScript loaded successfully")
