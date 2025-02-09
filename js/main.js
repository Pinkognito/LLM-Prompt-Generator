console.log("JS file main.js is loading.");
document.addEventListener("DOMContentLoaded", function() {
  console.log("DOM fully loaded.");
  loadOptions();
  loadParentPrefabs();
  loadPrefabs();
  document.getElementById("applyPrefabBtn").addEventListener("click", function() {
    console.log("Apply Prefab button clicked.");
    applyPrefab();
  });
  document.getElementById("deletePrefabBtn").addEventListener("click", function() {
    console.log("Delete Prefab button clicked.");
    deletePrefab();
  });
  document.getElementById("generatePromptBtn").addEventListener("click", function() {
    console.log("Generate Prompt button clicked.");
    generatePrompt();
  });
  document.getElementById("savePrefabBtn").addEventListener("click", function() {
    console.log("Save Prefab button clicked.");
    savePrefab();
  });
  document.getElementById("updatePrefabBtn").addEventListener("click", function() {
    console.log("Update Prefab button clicked.");
    updatePrefab();
  });
});
function loadOptions() {
  console.log("Function loadOptions() started.");
  fetch('api/get_options.php')
    .then(response => {
      console.log("Response from get_options.php:", response.status, response.statusText);
      return response.json();
    })
    .then(data => {
      console.log("Options data received (formatted):\n", JSON.stringify(data, null, 2));
      let container = document.getElementById("optionsContainer");
      container.innerHTML = "";
      for (let category in data) {
        console.log("Processing category:", category);
        let detailsEl = document.createElement("details");
        detailsEl.classList.add("option-category");
        let summaryEl = document.createElement("summary");
        summaryEl.textContent = category;
        detailsEl.appendChild(summaryEl);
        data[category].forEach(function(opt) {
          console.log("Processing option in category " + category + ":\n", JSON.stringify(opt, null, 2));
          let label = document.createElement("label");
          label.style.display = "block";
          label.innerHTML = `<input type="checkbox" name="option_${opt.id}" value="${opt.id}"> ${opt.display_name}`;
          detailsEl.appendChild(label);
        });
        container.appendChild(detailsEl);
      }
      console.log("Function loadOptions() completed.");
    })
    .catch(error => console.error("Error in loadOptions():", error));
}
function loadParentPrefabs() {
  console.log("Function loadParentPrefabs() started.");
  fetch('api/get_prefabs.php')
    .then(response => {
      console.log("Response from get_prefabs.php (Parent):", response.status, response.statusText);
      return response.json();
    })
    .then(data => {
      console.log("Parent prefabs data received (formatted):\n", JSON.stringify(data, null, 2));
      let flatList = flattenPrefabs(data);
      console.log("Flattened list of parent prefabs (formatted):\n", JSON.stringify(flatList, null, 2));
      // Since the dropdown is no longer visible, we only use the hidden field.
      // Logging the flat list for debugging purposes.
      console.log("Function loadParentPrefabs() completed.");
    })
    .catch(error => console.error("Error in loadParentPrefabs():", error));
}
function flattenPrefabs(tree) {
  console.log("Function flattenPrefabs() started.");
  let list = [];
  tree.forEach(function(node) {
    let nameVal = node.text || node.name;
    list.push({ id: node.id, name: nameVal });
    if (node.children && node.children.length > 0) {
      list = list.concat(flattenPrefabs(node.children));
    }
  });
  console.log("Function flattenPrefabs() completed. Result:\n", JSON.stringify(list, null, 2));
  return list;
}
function loadPrefabs() {
  console.log("Function loadPrefabs() started.");
  fetch('api/get_prefabs.php')
    .then(response => {
      console.log("Response from get_prefabs.php (Prefabs):", response.status, response.statusText);
      return response.json();
    })
    .then(data => {
      console.log("Prefabs data received (formatted):\n", JSON.stringify(data, null, 2));
      if (!Array.isArray(data)) {
        data = [data];
      }
      function transformNodes(nodes) {
        if (!Array.isArray(nodes)) return [];
        return nodes.map(function(node) {
          return {
            id: node.id,
            text: node.name,
            selectedOptions: node.selectedOptions ? node.selectedOptions : [],
            parent_id: node.parent_id,
            children: node.children ? transformNodes(node.children) : []
          };
        });
      }
      var treeData = transformNodes(data);
      console.log("Transformed tree data (formatted):\n", JSON.stringify(treeData, null, 2));
      $('#prefabTree').jstree("destroy");
      $('#prefabTree').jstree({
        'core': {
          'data': treeData,
          'check_callback': true,
          'themes': {
            'icons': false
          }
        }
      }).on("changed.jstree", function (e, data) {
        console.log("jsTree 'changed' event triggered. Selected IDs:\n", JSON.stringify(data.selected, null, 2));
        if (data.selected.length) {
          let node = data.instance.get_node(data.selected[0]);
          console.log("Selected node (formatted):\n", JSON.stringify(node, null, 2));
          // Reset the options checkboxes so that only the currently selected options are set.
          document.querySelectorAll("#optionsContainer input[type='checkbox']").forEach(chk => chk.checked = false);
          // Set the hidden field for the parent ID based on the currently selected prefab.
          document.getElementById("parentPrefabId").value = node.id;
          console.log("Parent prefab (from TreeView) set (only ID):\n", JSON.stringify({ name: node.text, id: node.id }, null, 2));
          applyPrefabFromNode(node);
        }
      });
      console.log("Function loadPrefabs() completed.");
    })
    .catch(error => console.error("Error in loadPrefabs():", error));
}
function resetForm() {
  console.log("Function resetForm() started.");
  document.getElementById("prefabName").value = "";
  // The parent field remains set to preserve the current parent.
  document.querySelectorAll("#optionsContainer input[type='checkbox']").forEach(chk => chk.checked = false);
  document.getElementById("promptOutput").value = "";
  console.log("Function resetForm() completed.");
}
function getSelectedPrefab() {
  var selected = $('#prefabTree').jstree('get_selected', true);
  console.log("Function getSelectedPrefab() output:\n", JSON.stringify(selected, null, 2));
  return selected.length ? selected[0] : null;
}
function applyPrefabFromNode(node) {
  console.log("Function applyPrefabFromNode() started for node:\n", JSON.stringify(node, null, 2));
  if (node.original.selectedOptions && Array.isArray(node.original.selectedOptions)) {
    node.original.selectedOptions.forEach(function(optId) {
      let checkbox = document.querySelector('input[value="' + optId + '"]');
      if (checkbox) {
        checkbox.checked = true;
        console.log("Option with ID " + optId + " activated.");
      }
    });
  } else {
    console.log("No selected options found in the node. All options have been reset.");
  }
}
function generatePrompt() {
  console.log("Function generatePrompt() started.");
  let promptText = "Please consider the following options:\n\n";
  let selectedOptions = [];
  document.querySelectorAll("#optionsContainer input[type='checkbox']:checked").forEach(function(chk) {
    selectedOptions.push(chk.parentNode.textContent.trim());
  });
  if (selectedOptions.length > 0) {
    promptText += "Options: " + selectedOptions.join(", ") + ".\n";
  }
  promptText += "\nIMPORTANT: Apply all the above options as an integral part of your behavior.";
  console.log("Generated prompt (formatted):\n", JSON.stringify(promptText, null, 2));
  document.getElementById("promptOutput").value = promptText;
}
function savePrefab() {
  console.log("Function savePrefab() started.");
  var prefabName = document.getElementById("prefabName").value.trim();
  if (!prefabName) {
    alert("Please enter a name for the prefab.");
    return;
  }
  var parentId = document.getElementById("parentPrefabId").value.trim();
  var selectedOptions = [];
  document.querySelectorAll("#optionsContainer input[type='checkbox']:checked").forEach(function(chk) {
    selectedOptions.push(chk.value);
  });
  var payload = { name: prefabName, options: selectedOptions };
  if (parentId) {
    payload.parent_id = parentId;
  }
  console.log("Payload for savePrefab (formatted):\n", JSON.stringify(payload, null, 2));
  fetch('api/prefab_actions.php?action=save', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(response => response.json())
  .then(data => {
    console.log("Server response (savePrefab, formatted):\n", JSON.stringify(data, null, 2));
    if (data.success) {
      alert("Prefab saved.");
      loadPrefabs();
      loadParentPrefabs();
    } else {
      alert("Error: " + data.message);
    }
  })
  .catch(error => {
    console.error("Error in savePrefab():", error);
    alert("An error occurred.");
  });
}
function updatePrefab() {
  console.log("Function updatePrefab() started.");
  var selectedNode = getSelectedPrefab();
  if (!selectedNode) {
    alert("Please select a prefab first.");
    return;
  }
  var prefabId = selectedNode.id;
  var prefabName = document.getElementById("prefabName").value.trim();
  var parentId = document.getElementById("parentPrefabId").value.trim();
  var selectedOptions = [];
  document.querySelectorAll("#optionsContainer input[type='checkbox']:checked").forEach(function(chk) {
    selectedOptions.push(chk.value);
  });
  var payload = { id: prefabId, name: prefabName, options: selectedOptions };
  if (parentId) {
    payload.parent_id = parentId;
  }
  console.log("Payload for updatePrefab (formatted):\n", JSON.stringify(payload, null, 2));
  fetch('api/prefab_actions.php?action=update', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(response => response.json())
  .then(data => {
    console.log("Server response (updatePrefab, formatted):\n", JSON.stringify(data, null, 2));
    if (data.success) {
      alert("Prefab updated.");
      loadPrefabs();
      loadParentPrefabs();
    } else {
      alert("Error: " + data.message);
    }
  })
  .catch(error => {
    console.error("Error in updatePrefab():", error);
    alert("An error occurred.");
  });
}
function deletePrefab() {
  console.log("Function deletePrefab() started.");
  var selectedNode = getSelectedPrefab();
  if (!selectedNode) {
    alert("Please select a prefab first.");
    return;
  }
  var prefabId = selectedNode.id;
  if (!confirm("Do you really want to delete the prefab?")) return;
  console.log("Deleting prefab with ID:", prefabId);
  fetch('api/prefab_actions.php?action=delete', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: prefabId })
  })
  .then(response => response.json())
  .then(data => {
    console.log("Server response (deletePrefab, formatted):\n", JSON.stringify(data, null, 2));
    if (data.success) {
      alert("Prefab deleted.");
      loadPrefabs();
      loadParentPrefabs();
      resetForm();
    } else {
      alert("Error: " + data.message);
    }
  })
  .catch(error => {
    console.error("Error in deletePrefab():", error);
    alert("An error occurred.");
  });
}
