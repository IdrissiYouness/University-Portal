function handleFileChange() {
    const fileInput = document.getElementById('real-file');
    if (fileInput.files.length > 0) {
      alert(`Selected file: ${fileInput.files[0].name}`);
    }
}