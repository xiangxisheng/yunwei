window.index = function() {
  new Vue({
    el: '#app',
    created: function() {
      this.update()
    },
    data: {
      rows: []
    },
    methods: {
      click1: function () {
        this.update()
      },
      update: function () {
        var request = {}
        request.rnd = 123;
        // const that = this
        this.$http.get('http://192.168.221.41/json.php', request).then(function (res) {
          this.rows = res.data
          /*
          for (var i = 0; i < res.data.length; i++) {
            this.rows
          }*/
          /*
          $("#example1").DataTable({
            "paging": false,
            "lengthChange": true,
            "searching": false,
            "ordering": false,
            "info": true,
            "autoWidth": true
          });//*/
        }, function (res) {
          alert(res.status)
        })
      }
    }
  })
}
