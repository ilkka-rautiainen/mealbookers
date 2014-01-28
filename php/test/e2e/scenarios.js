'use strict';

/* http://docs.angularjs.org/guide/dev_guide.e2e-testing */

angular.scenario.matcher('urlEqualsFuture', function(expectedFuture) {
  // Parse the leading '/' (by default, this.actual is e.g. /Test0, when expectedFuture.value is Test0)
  return this.actual.substring(1) === expectedFuture.value;
});


describe('Mealbookers', function() {

  beforeEach(function() {
    browser().navigateTo('/');
  });


  it('should automatically redirect to /menu when location hash/fragment is empty', function() {
    expect(browser().location().url()).toBe('/menu');
  });


  describe('menu', function() {
    beforeEach(function() {
      browser().navigateTo('#/menu');
    });

    it('should render restaurant list', function() {

      var firstItem = element('#restaurantList li:nth-child(1) div.restaurantName');

      expect(firstItem.html()).not().toBe(null);
    });

  });

});
