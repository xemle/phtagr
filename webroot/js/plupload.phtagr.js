var phtagr = phtagr || {};
phtagr.upload = phtagr.upload || {};

phtagr.upload.File = Backbone.Model.extend({
  url: '#',
  save: function() { return true }
});
phtagr.upload.Media = Backbone.Model.extend({
  url: '#',
  save: function() { return true }
});
phtagr.upload.Files = Backbone.Collection.extend({
  model: phtagr.upload.File,
  percent: 0,
  size: 0,
  loadedFiles: 0,
  started: 0,
  currentId: '',

  initialize: function() {
    _.bindAll(this, 'update', 'getEstimate', 'getCurrent');
    this.on('add', this.update, this);
  },
  update: function() {
    if (this.started == 0) {
      this.started = new Date().getTime();
    }
    var loaded = 0;
    var size = 0;
    var loadedFiles = 0;
    this.each(function(file) {
      loaded += file.get('file').loaded;
      size += file.get('file').size;
      if (file.get('file').loaded) {
        loadedFiles++;
      };
    })
    this.size = size;
    this.percent = 100 * loaded / size;
    this.loadedFiles = loadedFiles;
    this.trigger('change');
    if (this.percent == 100) {
      this.trigger('complete');
      this.reset();
    }
  },
  getEstimate: function() {
    var seconds = (new Date().getTime() - this.started) / 1000;
    return Math.ceil(seconds * (100 - this.percent) / (this.percent + 0.01));
  },
  getCurrent: function() {
    var current = null;
    this.each(function(file) {
      if (file.get('file').id == this.currentId) {
        current = file;
      };
    }, this);
    return current;
  }
});
phtagr.upload.MediaList = Backbone.Collection.extend({
  model: phtagr.upload.Media
});
phtagr.upload.SingleUploadView = Backbone.View.extend({
  text: 'Uploading file %s',

  initialize: function(options) {
    this.text = options.text || this.text
    this.collection.on('change', this.update, this);
    this.collection.on('complete', this.hide, this);
  },
  update: function() {
    var current = this.collection.getCurrent();
    if (current) {
      this.$('p').text(this.text.replace(/%s/, current.get('file').name));
      this.$('.progress').progressbar({value: current.get('file').percent});
    }
    $(this.el).show();
  },
  hide: function() {
    $(this.el).hide(300);
  }
});
phtagr.upload.AllUploadView = Backbone.View.extend({
  text: 'Upload file %d/%d. Estimate %s.',

  initialize: function(options) {
    this.text = options.text || this.text
    this.collection.on('change', this.update, this);
    this.collection.on('complete', this.hide, this);
  },
  update: function() {
    this.$('p').text(this.text.replace(/%d/, this.collection.loadedFiles)
      .replace(/%d/, this.collection.length)
      .replace(/%s/, this.formatTime(this.collection.getEstimate())));
    this.$('.progress').progressbar({value: this.collection.percent});
    $(this.el).show();
  },
  hide: function() {
    $(this.el).hide(300);
  },
  formatTime: function(time) {
    var seconds = time % 60;
    var minutes = Math.floor((time / 60) % 60);
    var hours = Math.floor(time / 3600);
    var result = '';
    if (hours > 9) {
      result += hours + ':';
    } else if (hours > 0) {
      result += '0' + hours + ':';
    }
    if (minutes > 9) {
      result += minutes + ':';
    } else {
      result += '0' + minutes + ':';
    }
    if (seconds > 9) {
      result += seconds;
    } else {
      result += '0' + seconds;
    }
    return result;
  }
});
phtagr.upload.MediaView = Backbone.View.extend({
  baseUrl: '',
  thumbPrefix: 'media/mini/',
  linkPrefix: 'images/view/',
  crumbs: '', 
  showCount: 24,
  oldIndex: -1,

  initialize: function(options) {
    this.baseUrl = options.baseUrl || this.baseUrl
    this.showCount = options.showCount || this.showCount
    this.crumbs = options.crumbs || this.crumbs
    this.collection.on('add', this.update, this);
  },
  update: function() {
    if (this.collection.length) {
      $(this.el).show();
    }
    var end = Math.min(this.showCount, this.collection.length) - 1;
    var thumbs = this.$('.thumbs');
    for (var i = this.oldIndex + 1; i <= end; i++) {
      var media = this.collection.at(i);
      var link = this.baseUrl + this.linkPrefix + media.get('id') + this.crumbs;
      var thumb = this.baseUrl + this.thumbPrefix + media.get('id');
      thumbs.append('<a href="' + link + '"><img src="' + thumb + '" width="75" height="75"/></a>');
    }
    this.oldIndex = end;
  }

});

phtagr.upload.initUploader = function(options, fileCollection, mediaCollection, callback) {
  var uploader = new plupload.Uploader(options);

  if (typeof(callback) == 'function') {
    uploader.bind('Init', callback);
  }

  uploader.init();

  uploader.bind('FilesAdded', function(up, files) {
    $.each(files, function(i, file) {
      fileCollection.create({'file': file});
    });
    $('#filelist').show();
    uploader.start();
    up.refresh(); // Reposition Flash/Silverlight
  });

  uploader.bind('UploadProgress', function(up, file) {
    fileCollection.currentId = file.id;
    fileCollection.update();
  });
  uploader.bind('FileUploaded', function(up, file, response) {
    fileCollection.currentId = file.id;
    fileCollection.update();
    var result = $.parseJSON(response.response);
    if (result.mediaIds) {
      var len = result.mediaIds.length;
      for (var i = 0; i < len; i++) {
        mediaCollection.create({'id': result.mediaIds[i]});
      }
    }
  });

  uploader.bind('Error', function(up, err) {
    $('#filelist').append("<div>Error: " + err.code +
      ", Message: " + err.message +
      (err.file ? ", File: " + err.file.name : "") +
      "</div>"
    );

    up.refresh(); // Reposition Flash/Silverlight
  });

};
