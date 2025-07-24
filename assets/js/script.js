// Global News Network JavaScript File
// BACKEND DEVELOPER: This file contains all the JavaScript functions for the website

// Theme toggle functionality
function toggleTheme() {
  console.log("Toggling theme...") // Debug message

  var body = document.body
  var theme_icon = document.getElementById("themeIcon")

  // Check current theme and switch
  if (body.getAttribute("data-theme") === "dark") {
    // Switch to light mode
    body.removeAttribute("data-theme")
    theme_icon.className = "fas fa-moon"
    localStorage.setItem("theme", "light")
    console.log("Switched to light mode")
  } else {
    // Switch to dark mode
    body.setAttribute("data-theme", "dark")
    theme_icon.className = "fas fa-sun"
    localStorage.setItem("theme", "dark")
    console.log("Switched to dark mode")
  }
}

// Load saved theme when page loads
document.addEventListener("DOMContentLoaded", () => {
  console.log("Page loaded, checking saved theme...")

  var saved_theme = localStorage.getItem("theme")
  var theme_icon = document.getElementById("themeIcon")

  if (saved_theme === "dark") {
    document.body.setAttribute("data-theme", "dark")
    if (theme_icon) {
      theme_icon.className = "fas fa-sun"
    }
    console.log("Applied saved dark theme")
  } else {
    console.log("Using default light theme")
  }
})

// Newsletter subscription function
function handleNewsletterSignup(event) {
  event.preventDefault() // Stop form from submitting normally

  console.log("Newsletter signup form submitted")

  var form = event.target
  var email = form.querySelector('input[name="email"]').value

  // Basic email validation
  if (!email || !email.includes("@")) {
    alert("Please enter a valid email address")
    return false
  }

  // Show success message
  var success_message = document.getElementById("newsletter-success")
  if (success_message) {
    success_message.style.display = "block"
    form.reset() // Clear the form

    // Hide success message after 5 seconds
    setTimeout(() => {
      success_message.style.display = "none"
    }, 5000)
  }

  // BACKEND DEVELOPER: Send the email to your server
  // You can use fetch() or XMLHttpRequest to send data to newsletter_signup.php
  console.log("Email to subscribe: " + email)

  return false // Prevent normal form submission for now
}

// Quick newsletter subscription (sidebar)
function handleQuickSubscribe(event) {
  event.preventDefault()

  console.log("Quick subscribe form submitted")

  var form = event.target
  var email = form.querySelector('input[name="email"]').value

  // Basic validation
  if (!email || !email.includes("@")) {
    alert("Please enter a valid email address")
    return false
  }

  // Show success message
  alert("Thank you for subscribing! You will receive daily news updates.")
  form.reset()

  // BACKEND DEVELOPER: Send to server
  console.log("Quick subscribe email: " + email)

  return false
}

// Search functionality
function searchArticles() {
  var search_input = document.getElementById("searchInput")
  var search_term = search_input.value.trim()

  if (search_term) {
    console.log("Searching for: " + search_term)
    // Redirect to search page
    window.location.href = "search.php?search_query=" + encodeURIComponent(search_term)
  } else {
    alert("Please enter a search term")
  }
}

// Handle search on Enter key press
document.addEventListener("DOMContentLoaded", () => {
  var search_input = document.getElementById("searchInput")
  if (search_input) {
    search_input.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        e.preventDefault()
        searchArticles()
      }
    })
  }
})

// Authentication functions - BACKEND DEVELOPER: Connect these to your login system
function showLogin() {
  console.log("Redirecting to login page")
  window.location.href = "login.php"
}

function showSignup() {
  console.log("Redirecting to signup page")
  window.location.href = "register.php"
}

// Utility function to format numbers
function formatNumber(num) {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + "M"
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + "K"
  }
  return num.toString()
}

// Function to update view counts (for articles)
function updateViewCount(article_id) {
  // BACKEND DEVELOPER: Send request to update view count
  console.log("Updating view count for article: " + article_id)

  var xhr = new XMLHttpRequest()
  xhr.open("POST", "update_views.php", true)
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded")
  xhr.send("article_id=" + article_id)
}

// Function to handle article clicks
function goToArticle(article_id) {
  console.log("Going to article: " + article_id)
  updateViewCount(article_id)
  window.location.href = "article.php?id=" + article_id
}

// Simple animation for cards on hover (optional enhancement)
document.addEventListener("DOMContentLoaded", () => {
  var article_cards = document.querySelectorAll(".article-card")

  article_cards.forEach((card) => {
    card.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-5px)"
    })

    card.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0)"
    })
  })
})

// Function to handle mobile menu (if you add one later)
function toggleMobileMenu() {
  var nav = document.querySelector("nav ul")
  if (nav.style.display === "none" || nav.style.display === "") {
    nav.style.display = "flex"
    nav.style.flexDirection = "column"
  } else {
    nav.style.display = "none"
  }
}

// Simple loading indicator function
function showLoading() {
  console.log("Showing loading...")
  // You can add a loading spinner here
}

function hideLoading() {
  console.log("Hiding loading...")
  // Hide loading spinner
}

// Function to check if user is online
function checkOnlineStatus() {
  if (navigator.onLine) {
    console.log("User is online")
    return true
  } else {
    console.log("User is offline")
    alert("You appear to be offline. Some features may not work properly.")
    return false
  }
}

// Check online status when page loads
document.addEventListener("DOMContentLoaded", () => {
  checkOnlineStatus()

  // Listen for online/offline events
  window.addEventListener("online", () => {
    console.log("User came back online")
  })

  window.addEventListener("offline", () => {
    console.log("User went offline")
  })
})

// Simple function to scroll to top
function scrollToTop() {
  window.scrollTo({
    top: 0,
    behavior: "smooth",
  })
}

// Add scroll to top button functionality
document.addEventListener("DOMContentLoaded", () => {
  // Create scroll to top button (you can add this to your HTML too)
  var scroll_button = document.createElement("button")
  scroll_button.innerHTML = '<i class="fas fa-arrow-up"></i>'
  scroll_button.className = "scroll-to-top"
  scroll_button.style.cssText =
    "position: fixed; bottom: 20px; right: 20px; background: var(--primary-color); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; cursor: pointer; display: none; z-index: 1000;"
  scroll_button.onclick = scrollToTop
  document.body.appendChild(scroll_button)

  // Show/hide scroll button based on scroll position
  window.addEventListener("scroll", () => {
    if (window.pageYOffset > 300) {
      scroll_button.style.display = "block"
    } else {
      scroll_button.style.display = "none"
    }
  })
})

// Console message for developers
console.log("Global News Network JavaScript loaded successfully!")
console.log("BACKEND DEVELOPER: Remember to connect all functions to your PHP backend")
