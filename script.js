// Theme Toggle Functionality
function toggleTheme() {
  const body = document.body
  const themeIcon = document.getElementById("themeIcon")

  if (body.getAttribute("data-theme") === "dark") {
    body.removeAttribute("data-theme")
    themeIcon.className = "fas fa-moon"
    localStorage.setItem("theme", "light")
  } else {
    body.setAttribute("data-theme", "dark")
    themeIcon.className = "fas fa-sun"
    localStorage.setItem("theme", "dark")
  }
}

// Load saved theme on page load
document.addEventListener("DOMContentLoaded", () => {
  const savedTheme = localStorage.getItem("theme")
  const themeIcon = document.getElementById("themeIcon")

  if (savedTheme === "dark") {
    document.body.setAttribute("data-theme", "dark")
    if (themeIcon) {
      themeIcon.className = "fas fa-sun"
    }
  }
})

// Newsletter Subscription
function subscribeNewsletter(event) {
  event.preventDefault()
  const email = document.getElementById("newsletterEmail").value
  const successMessage = document.getElementById("newsletterSuccess")

  // Simulate API call
  setTimeout(() => {
    successMessage.style.display = "block"
    document.getElementById("newsletterEmail").value = ""

    // Hide success message after 5 seconds
    setTimeout(() => {
      successMessage.style.display = "none"
    }, 5000)
  }, 500)
}

// Quick Newsletter Subscription
function quickSubscribe(event) {
  event.preventDefault()
  const form = event.target
  const email = form.querySelector('input[type="email"]').value

  // Simulate API call
  setTimeout(() => {
    alert("Thank you for subscribing! You will receive daily news updates.")
    form.reset()
  }, 500)
}

// Comment System
function submitComment(event) {
  event.preventDefault()
  const name = document.getElementById("commentName").value
  const email = document.getElementById("commentEmail").value
  const text = document.getElementById("commentText").value
  const successMessage = document.getElementById("commentSuccess")
  const commentsList = document.getElementById("commentsList")

  // Create new comment element
  const newComment = document.createElement("div")
  newComment.className = "comment"
  newComment.innerHTML = `
        <div class="comment-header">
            <span class="comment-author">${name}</span>
            <span class="comment-date">Just now</span>
        </div>
        <div class="comment-text">${text}</div>
        <div class="comment-actions">
            <button class="comment-action" onclick="likeComment(this)">
                <i class="fas fa-thumbs-up"></i> Like (0)
            </button>
            <button class="comment-action" onclick="replyToComment(this)">
                <i class="fas fa-reply"></i> Reply
            </button>
        </div>
    `

  // Add new comment to the top of the list
  if (commentsList) {
    commentsList.insertBefore(newComment, commentsList.firstChild)
  }

  // Show success message
  if (successMessage) {
    successMessage.style.display = "block"

    // Hide success message after 5 seconds
    setTimeout(() => {
      successMessage.style.display = "none"
    }, 5000)
  }

  // Reset form
  document.getElementById("commentName").value = ""
  document.getElementById("commentEmail").value = ""
  document.getElementById("commentText").value = ""

  // Update comment count
  const commentCount = document.getElementById("commentCount")
  const commentsCount = document.getElementById("commentsCount")
  if (commentCount) {
    const currentCount = Number.parseInt(commentCount.textContent) || 0
    commentCount.textContent = currentCount + 1
  }
  if (commentsCount) {
    const currentCount = Number.parseInt(commentsCount.textContent) || 0
    commentsCount.textContent = currentCount + 1
  }
}

// Like Comment
function likeComment(button) {
  const likeText = button.innerHTML
  const currentLikes = Number.parseInt(likeText.match(/$$(\d+)$$/)[1])
  const newLikes = currentLikes + 1
  button.innerHTML = `<i class="fas fa-thumbs-up"></i> Like (${newLikes})`
  button.style.color = "var(--accent-color)"
  button.disabled = true
}

// Reply to Comment
function replyToComment(button) {
  alert("Reply functionality would be implemented here!")
}

// Search Functionality
function searchArticles() {
  const searchTerm = document.getElementById("searchInput").value
  if (searchTerm.trim()) {
    alert(`Searching for: "${searchTerm}"\n\nSearch functionality would be implemented here!`)
  }
}

// Auth Functions
function showLogin() {
  alert("Login functionality would be implemented here!")
}

function showSignup() {
  alert("Sign up functionality would be implemented here!")
}

// Search on Enter key
document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("searchInput")
  if (searchInput) {
    searchInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        searchArticles()
      }
    })
  }
})
